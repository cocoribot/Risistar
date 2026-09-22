<?php
require __DIR__.'/bootstrap.php';
$store=new TelemetryStore(TelemetryConnection::open());
$s=TelemetrySettings::defaults();$s['enabled']=1;$now=time();
$report=[];
require ROOT_PATH.'includes/vars.php';
$LNG=new Language('fr');$LNG->includeData(['L18N','INGAME','TECH']);
foreach (['telemetry_events','telemetry_daily','telemetry_warnings'] as $table) { $store->query('DELETE FROM '.$table); }
TelemetryStore::health(['events_removed_before'=>0,'daily_removed_before'=>0,'gaps'=>[]]);
foreach ([50,200] as $population) {
    $base=1;
    for ($id=51;$id<=$population;++$id) {
        PlayerUtil::createPlayer(1,'Load'.$id,PlayerUtil::cryptPassword(getenv('TELEMETRY_TEST_PASSWORD')),
            'load'.$id.'@example.test','fr',2,10+$id,5);
    }
    $start=microtime(true);$events=0;
    for ($actor=$base;$actor<$base+$population;++$actor) {
        $store->query('DELETE FROM telemetry_events WHERE universe=1 AND actor=? AND at>=?',[$actor,$now-8*86400]);
        $batch=[];
        for ($day=0;$day<7;++$day) {
            for ($i=0;$i<100;++$i) {
                $kind=['galaxy.view','fleet.send','queue.buildings'][$i%3];
                $batch[]=event($actor,$now-($day+1)*86400+$i*400,$kind,($actor%$population)+1,['galaxy'=>1,'system'=>10+$i%20,'client'=>$i%2 ? 'Chrome · Android · mobile' : 'Firefox · Windows · ordinateur']);
                ++$events;
                if (count($batch)===200) {$store->write($batch,[1=>$s]);$batch=[];}
            }
        }
        $batch[]=event($actor,$now-3*86400,'delivery',($actor%$population)+1,['metal'=>10000000]);
        ++$events;
        if ($batch) {$store->write($batch,[1=>$s]);}
    }
    $writeSeconds=microtime(true)-$start;
    $start=microtime(true);$queries=$store->queries;$warnings=0;
    $cursor=0;$pairA=0;$pairB=0;$batches=[];
    $review=new TelemetryReview($store);
    do {
        $result=$review->evaluate(1,$s,$now,$cursor,$pairA,$pairB);
        $cursor=$result['cursor'];$pairA=$result['pair_a'];$pairB=$result['pair_b'];
        $warnings+=$result['findings'];$batches[]=$result;
        check(count($batches)<=ceil($population/$s['analysis_accounts'])+1,'bounded review pagination');
        check(!$result['truncated'],'load evidence is complete');
    } while ($result['incomplete']);
    $analysisSeconds=microtime(true)-$start;
    $analysisQueries=$store->queries-$queries;
    check((int)$store->query("SELECT COUNT(*) FROM telemetry_warnings WHERE universe=1 AND kind LIKE 'pushing.%'")->fetchColumn()>=$population,'full pipeline saves exchange warnings');
    $start=microtime(true);$health=$store->maintenance($s,$now);
    $fileBytes=(int)Database::get()->getHandle()->query("SELECT SUM(FILE_SIZE) FROM information_schema.INNODB_TABLESPACES WHERE NAME LIKE 'telemetry_test/%'")->fetchColumn();
    check($health['allocated_mb']*1048576 >= $fileBytes*0.7,'storage estimate tracks allocated MySQL files');
    $report[]=['accounts'=>$population,'events'=>$events,'write_seconds'=>$writeSeconds,'analysis_seconds'=>$analysisSeconds,
        'analysis_queries'=>$analysisQueries,'batches'=>count($batches),'slowest_batch_seconds'=>max(array_column($batches,'seconds')),'warnings'=>$warnings,'cleanup_seconds'=>microtime(true)-$start,
        'allocated_mb'=>$health['allocated_mb'],'mysql_file_mb'=>$fileBytes/1048576,'rows_estimate'=>$health['estimated_rows']];
    check($analysisQueries<=$population*12+count($batches),'bounded full review query count');
    check(max(array_column($batches,'seconds'))<10,'each review page completes within ten seconds');
    check($analysisSeconds<60,'headroom analysis completes within 60 seconds');
}
$plan=$store->query('EXPLAIN SELECT * FROM telemetry_events WHERE universe=1 AND actor=1 AND at>=? ORDER BY at,id LIMIT 10001',
    [$now-7*86400])->fetchAll(PDO::FETCH_ASSOC);
check($plan[0]['key']==='actor_time','account scan uses matching index');
$report['account_query_plan']=$plan;
$start=microtime(true);$queries=$store->queries;
foreach ([1,2] as $actor) {
    $counts=$store->actionCounts(1,$actor,$now-7*86400,$now);
    $network=$store->network(1,$actor,$now-7*86400,$now);
    check($network['ips']===1 && $network['clients']===2,'comparison aggregates full retained history');
}
$report['comparison']=['seconds'=>microtime(true)-$start,'queries'=>$store->queries-$queries];
check($report['comparison']['queries']===6 && $report['comparison']['seconds']<2,'comparison summary bounded');
$health=TelemetryStore::health();
$pressure=$s;$pressure['reserve_mb']=100;$pressure['budget_mb']=max(100,(int)ceil($health['allocated_mb'])+99);
$health=$store->maintenance($pressure,$now);
check($health['suspended'] && $health['effective_event_days']===1,'allocated storage triggers suspension and shorter retention');
$before=(int)$store->query('SELECT COUNT(*) FROM telemetry_events WHERE universe=1 AND actor=9999')->fetchColumn();
$store->write([event(9999,$now,'fleet.send')],[1=>$s]);
check((int)$store->query('SELECT COUNT(*) FROM telemetry_events WHERE universe=1 AND actor=9999')->fetchColumn()===$before,'events suspended before reserve');
check(count($store->daily(1,9999,$now-86400))===1,'compact activity survives event suspension');
$store->maintenance($s,$now);
file_put_contents(__DIR__.'/results/load.json',json_encode($report,JSON_PRETTY_PRINT));
echo json_encode($report,JSON_PRETTY_PRINT)."\n";
