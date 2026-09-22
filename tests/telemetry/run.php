<?php
require __DIR__.'/bootstrap.php';
$s=TelemetrySettings::defaults();$s['enabled']=1;
$now=1789992000;
$store=new TelemetryStore(TelemetryConnection::open());
foreach (['telemetry_events','telemetry_daily','telemetry_warnings'] as $table) {
    $store->query("DELETE FROM $table WHERE universe=1 AND actor IN (901,902,903,910)");
}
$results=[];
$workflows=[];
foreach (['fixed','varied','breaks','extra'] as $scenario) {
    $rows=[];$at=$now-50000;
    for ($loop=0;$loop<20;++$loop) {
        foreach (['galaxy.view','fleet.send','queue.buildings'] as $step) {
            $rows[]=event(901,$at,$step,902);
            $at += $scenario==='fixed'?30:([17,89,42,153,31][($loop+count($rows))%5]);
        }
        if ($scenario==='breaks' && $loop%5===0) {$at+=1800;}
        if ($scenario==='extra' && $loop%3===0) {$rows[]=event(901,$at++,'account.officer');}
    }
    $warnings=TelemetryDetectors::automation($rows,$s,$now);
    check(in_array('workflow',kinds($warnings)),'workflow '.$scenario);
    if ($scenario==='fixed') {check(in_array('timing',kinds($warnings)),'fixed timing');}
    $workflows[$scenario]=$rows;
    $results[$scenario]=kinds($warnings);
}
$reads=[];
for ($i=0;$i<50;++$i) {$reads[]=event(901,$now-20000+$i*180,'galaxy.view',0,['galaxy'=>1,'system'=>12]);}
check(in_array('polling',kinds(TelemetryDetectors::automation($reads,$s,$now))),'unchanged polling');
$human=[];
foreach ([0,47,302,811,991,2507,3901] as $delta) {$human[]=event(901,$now-5000+$delta,'fleet.send');}
check(!TelemetryDetectors::automation($human,$s,$now),'short irregular phone checks');
// One-minute intervals are merged by interaction timestamps, never by action count.
$base=strtotime('2026-09-18 14:00 UTC');
check(TelemetryActivity::intervals(TelemetryActivity::merge([[$base,$base]]),$base,$base+3600)===[[$base,$base+60]],'lone refresh contributes one minute');
$burst=[];
for ($i=0;$i<120;++$i) {$at=$base+(int)round($i*600/119);$burst[]=[$at,$at];}
check(TelemetryActivity::intervals(TelemetryActivity::merge(array_reverse($burst)),$base,$base+3600)===[[$base,$base+660]],'120 interactions across ten minutes count eleven minutes');
check(TelemetryActivity::merge([[$base+601,$base+601],[$base,$base],[$base+300,$base+300]])===[[$base,$base+300],[$base+601,$base+601]],'five-minute gap joins; five minutes and one second splits');
$days=[];
for ($d=1;$d<=4;++$d) {
    $at=strtotime(gmdate('Y-m-d',$now-$d*86400).' UTC');
    $days[]=['day'=>gmdate('Y-m-d',$at),'windows'=>json_encode([[$at,$at+21*3600-60]])];
}
$availability=TelemetryDetectors::availability($days,$s,$now);
check($availability[0]['kind']==='availability' && $availability[0]['strength']==='moderate','availability distinct');
check($availability[0]['metrics']['days'][0]['active_seconds']===21*3600,'availability uses estimated window duration');
check(TelemetryDetectors::availability([$days[0]],$s,$now)[0]['strength']==='weak','one long session is weak');
$at=strtotime($days[0]['day'].' UTC');$sparse=[];
for ($hour=0;$hour<24;++$hour) {$sparse[]=[$at+$hour*3600,$at+$hour*3600];}
check(!TelemetryDetectors::availability([['day'=>$days[0]['day'],'windows'=>json_encode($sparse)]],$s,$now),'one refresh per hour is only 24 active minutes');
$old=$days[0];$old['day']=gmdate('Y-m-d',$now-8*86400);$at=strtotime($old['day'].' UTC');$old['windows']=json_encode([[$at,$at+86340]]);
check(!TelemetryDetectors::availability([$old],$s,$now),'availability excludes days beyond completed lookback');
check(PlayerTelemetry::passive(['page'=>'buildings','passive_reload'=>'queue']),'automatic building reload');
check(PlayerTelemetry::passive(['page'=>'research','passive_reload'=>'queue']),'automatic research reload');
check(PlayerTelemetry::passive(['page'=>'overview','passive_reload'=>'queue']),'automatic overview reload');
check(PlayerTelemetry::passive(['page'=>'shipyard','passive_reload'=>'queue']),'automatic shipyard reload');
check(!PlayerTelemetry::passive(['page'=>'fleetTable']),'manual fleet table navigation');
check(!PlayerTelemetry::passive(['page'=>'fleetAjax','ajax'=>1]),'AJAX is not passivity');
$_SERVER['REMOTE_ADDR']='192.0.2.44';$_SERVER['HTTP_X_FORWARDED_FOR']='198.51.100.8';
check(Session::getClientIp()==='192.0.2.44','ignore forwarded address');
$delivery=event(901,$now-48*3600,'delivery',902,['metal'=>4000000,'crystal'=>0,'deuterium'=>0,'planet'=>42]);
$pending=$delivery;$pending['at']++;
check(TelemetryDetectors::pushing([$pending],901,902,[],$s,$now)[0]['strength']==='pending','one second before repayment deadline');
check(TelemetryDetectors::pushing([$delivery],901,902,[],$s,$now)[0]['strength']==='moderate','inclusive overdue boundary');
foreach ([2,3,4] as $rate) {
    $repay=event(902,$now-1,'delivery',901,['deuterium'=>4000000/$rate]);
    check(!TelemetryDetectors::pushing([$delivery,$repay],901,902,[],$s,$now),'fair permitted trade rate '.$rate);
}
$repay=event(902,$now-1,'delivery',901,['deuterium'=>750000]);
check(!TelemetryDetectors::pushing([$delivery,$repay],901,902,[],$s,$now),'25 percent tolerance inclusive');
$repay['data']['deuterium']=100000;
$partial=TelemetryDetectors::pushing([$delivery,$repay],901,902,[],$s,$now)[0];
check($partial['metrics']['deadline']===$delivery['at']+48*3600,'partial payment keeps deadline');
check(abs($partial['metrics']['balance']['remaining']-650000)<0.01,'partial payment exact balance');
check(!TelemetryDetectors::pushing([event(901,$now-200000,'fleet.send',902,['metal'=>9e9])],901,902,[],$s,$now),'launch/recall is not delivery');
$combat=event(901,$delivery['at']-300,'combat',902,['planet'=>42,'moon_chance'=>20]);
$moon=TelemetryDetectors::pushing([$delivery,$combat],901,902,[],$s,$now)[0];
check($moon['strength']==='uncertain' && $moon['metrics']['balance']['remaining']===750000.0,'moon context never adds a bill');
try {$invalid=$s;$invalid['event_days']=1;TelemetrySettings::validate($invalid);check(false,'invalid settings rejected');} catch (InvalidArgumentException $expected) {}
// Real MySQL: midnight, concurrent window updates, reader timestamps and passive deliveries.
$rows=[event(901,1790035199,'login'),event(901,1790035201,'fleet.send'),$delivery];
$store->write($rows,[1=>$s]);
$daily=$store->daily(1,901,1790030000);
check(count($daily)===2,'UTC midnight keeps two dates');
check(TelemetryActivity::intervals(TelemetryActivity::windows($daily),1790035000,1790035300)===[[1790035199,1790035261]],'activity merges across UTC midnight');
check(count($store->daily(1,902,0))===0,'delivery does not activate recipient');
$store->write($reads,[1=>$s]);
check((int)$store->query("SELECT COUNT(*) FROM telemetry_events WHERE universe=1 AND actor=901 AND kind='galaxy.view' AND at>=?",[$now-20000])->fetchColumn()===50,'all unchanged galaxy views retained');
$warning=$partial;
$store->warning(1,901,902,$warning,$s,$now);
$row=$store->query('SELECT id,settings FROM telemetry_warnings WHERE universe=1 AND actor=901 AND other=902')->fetch(PDO::FETCH_ASSOC);
$review=new TelemetryReview($store);
$review->decide(1,1,(int)$row['id'],'dismissed','Synthetic review',$now);
$changed=$s;$changed['push_minimum']=5000;
$store->warning(1,901,902,$warning,$changed,$now+1);
$saved=$review->evidence(1,(int)$row['id']);
check($saved['status']==='dismissed' && $saved['settings']['push_minimum']===100000 && $saved['latest_settings']['push_minimum']===5000,'repeat updates one case and preserves original settings/review');
check((int)$store->query('SELECT COUNT(*) FROM telemetry_warnings WHERE universe=1 AND actor=901 AND other=902')->fetchColumn()===1,'no duplicate warning');
$refreshRows=array_map(static function($event) { $event['actor']=903;return $event; },$workflows['fixed']);
$store->write($refreshRows,[1=>$s]);
$workflow=TelemetryDetectors::automation($workflows['fixed'],$s,$now)[1];
check($workflow['kind']==='workflow','workflow fixture selection');
$store->warning(1,903,0,$workflow,$s,$now);
$id=(int)$store->query("SELECT id FROM telemetry_warnings WHERE universe=1 AND actor=903 AND kind='workflow'")->fetchColumn();
$originalEvidence=$review->evidence(1,$id)['evidence'];
$review->decide(1,1,$id,'follow_up','Keep this decision',$now);
$fresh=$review->refresh(1,$id,$s,$now+1);
check($fresh['latest_evidence']['evaluated_at']===$now+1 && $fresh['latest_evidence']['matches'],'warning refresh reruns own check');
$strict=$s;$strict['workflow_min']=1000;
$fresh=$review->refresh(1,$id,$strict,$now+2);
check(!$fresh['latest_evidence']['matches'] && $fresh['evidence']===$originalEvidence && $fresh['status']==='follow_up','no longer matching preserves original evidence and decision');
require_once ROOT_PATH.'includes/classes/TelemetryPresentation.class.php';
$view=new TelemetryPresentation(new DateTimeZone('Europe/Paris'));
check(TelemetryPresentation::duration(73740)==='20 h 29 min' && TelemetryPresentation::duration(12660)==='3 h 31 min','long durations use hours minutes seconds');
check(TelemetryPresentation::duration(65)==='1 min 5 s' && TelemetryPresentation::duration(5)==='5 s' && TelemetryPresentation::duration(90000)==='1 j 1 h','short durations and totals retain their precision');
check(TelemetryPresentation::duration(0)==='0 s' && TelemetryPresentation::duration(1380)==='23 min','daily durations explicitly distinguish zero and minutes');
$durationRows=$view->rows(['active_seconds'=>73740,'largest_gap_seconds'=>12660,'seconds'=>[65,120]]);
check(array_column($durationRows,'value')===['20 h 29 min','3 h 31 min','1 min 5 s → 2 min'],'evidence durations use the same readable format');
check(count($view->timeline($availability[0]['timeline'])[0]['details'])===4,'availability warning retains and renders its activity windows');
$exchange=$view->exchange($partial['metrics']);
check(count($exchange['summary'])===5 && $exchange['summary'][2]['value']==='25 %','exchange shows a compact summary with readable tolerance');
check(!str_contains($exchange['summary'][3]['value'],'→') && str_contains($exchange['summary'][3]['value'],':'),'exchange rate uses M:C:D notation');
check(count($exchange['resources'])===2,'identical due and total deliveries are not repeated');
check($view->rows(['metal'=>5000000])[0]['value']===pretty_number(5000000),'resources use the game number format');
$from=strtotime('2026-09-18 00:00:00 Europe/Paris');
$to=strtotime('2026-09-20 23:59:59 Europe/Paris');
$activity=$view->activity([
    ['day'=>'2026-09-17','windows'=>json_encode([[$from-120,$from-120]])],
    ['day'=>'2026-09-18','windows'=>json_encode([[$from+120,$from+120],[$from+3600,$from+3600]])],
    ['day'=>'2026-09-20','windows'=>json_encode([[$from+2*86400+3600,$from+2*86400+3600]])],
],$from,$to);
check($activity['active_days']===2 && $activity['total_seconds']===300 && $activity['average_seconds']===100,'comparison clips midnight and averages durations over every selected day');
check(count($activity['days'])===3 && $activity['days']['2026-09-19']['seconds']===0,'comparison preserves empty days');
check($activity['days']['2026-09-18']['windows'][0]['range']==='00:00:00 – 00:03:00','comparison displays exact clipped window');
$at=strtotime('2026-10-25 00:15 UTC');
$fold=$view->activity([['day'=>'2026-10-25','windows'=>json_encode([[$at,$at],[$at+3600,$at+3600]])]],
    strtotime('2026-10-25 00:00 Europe/Paris'),strtotime('2026-10-25 23:59 Europe/Paris'));
check($fold['total_seconds']===120 && count($fold['days']['2026-10-25']['windows'])===2,'repeated local time retains both actual minutes');
check(count($fold['days']['2026-10-25']['slots'])===1 && str_contains($fold['days']['2026-10-25']['slots'][27]['date'],' / '),'overlapping heatmap windows share one cell');
$at=strtotime('2026-09-18 23:59:30 Europe/Paris');
$midnight=$view->activity([['day'=>'2026-09-18','windows'=>json_encode([[$at,$at]])]],$from,$to);
check($midnight['total_seconds']===60 && $midnight['days']['2026-09-18']['seconds']===30 && $midnight['days']['2026-09-19']['seconds']===30,'one minute splits at local midnight without double counting');
$live=$view->activity([['day'=>'2026-09-18','windows'=>json_encode([[$from+100,$from+100]])]],$from,$from+100);
check($live['total_seconds']===60,'latest interaction receives its full minute');

$counts=$store->actionCounts(1,901,$now-20000,$now);
check($counts['galaxy.view']===50,'summary counts galaxy views');
$desktop='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/140.0 Safari/537.36 Edg/140.0';
$mobile='Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/140.0 Mobile Safari/537.36';
check(PlayerTelemetry::client($desktop)==='Edge · Windows · ordinateur','desktop client family');
check(PlayerTelemetry::client($mobile)==='Chrome · Android · mobile','mobile client family');
$_SERVER['REMOTE_ADDR']='192.0.2.55';$_SERVER['HTTP_USER_AGENT']=$desktop;
PlayerTelemetry::record(910,1,'interaction',0,0,[],true,$now-90);
PlayerTelemetry::flush();
$_SERVER['REMOTE_ADDR']='2001:db8::55';$_SERVER['HTTP_USER_AGENT']=$mobile;
PlayerTelemetry::record(910,1,'login',0,0,[],true,$now-60);
PlayerTelemetry::record(910,1,'delivery',902,123,['metal'=>1000],false,$now-30);
PlayerTelemetry::flush();
$network=$store->network(1,910,$now-100,$now);
check($network['ips']===2 && $network['clients']===2 && count($network['rows'])===2,'IP and client aggregates use interactive requests');
check($network['rows'][0]['ip']==='2001:db8::55' && $network['rows'][0]['first_seen']===$now-60,'network timestamps and IPv6');
$serverEvent=$store->query("SELECT ip,data FROM telemetry_events WHERE actor=910 AND kind='delivery'")->fetch(PDO::FETCH_ASSOC);
check($serverEvent['ip']===null && !isset(json_decode($serverEvent['data'],true)['client']),'delivery never attributes cron caller IP or device to sender');
check(!TelemetryDetectors::readerActions([event(910,$now,'interaction')]),'network-only navigation does not duplicate detector actions');
// Transaction callbacks follow real MySQL savepoints.
$db=Database::get();$committed=[];
$db->beginTransaction();$db->afterCommit(static function()use(&$committed){$committed[]='outer';});
$db->beginTransaction();$db->afterCommit(static function()use(&$committed){$committed[]='rolled_back';});
$db->rollBack();$db->commit();
check($committed===['outer'],'rolled back savepoint discards evidence');
TelemetryStore::health(['failure'=>'synthetic_failure','gap_start'=>$now-100]);
TelemetryStore::health(['success'=>$now]);
check(count(TelemetryStore::health()['gaps'])>=1,'recovery retains failed-write interval');
$store->maintenance($s,$now+100*86400);
check((int)$store->query('SELECT COUNT(*) FROM telemetry_warnings WHERE universe=1 AND id=?',[$row['id']])->fetchColumn()===0,
    'old dismissed evidence follows configured retention');
$store->warning(1,901,902,$warning,$s,$now);
$openId=$store->query('SELECT id FROM telemetry_warnings WHERE universe=1 AND actor=901 AND other=902')->fetchColumn();
$store->maintenance($s,$now+100*86400);
check($review->evidence(1,(int)$openId)['evidence']!==null,'open evidence survives cleanup');
file_put_contents(__DIR__.'/results/scenarios.json',json_encode($results,JSON_PRETTY_PRINT));
echo "Detector, settings, MySQL storage and review assertions passed.\n";
