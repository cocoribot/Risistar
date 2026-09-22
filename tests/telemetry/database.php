<?php
require __DIR__.'/bootstrap.php';
$store=new TelemetryStore(TelemetryConnection::open());
$s=TelemetrySettings::defaults();$s['enabled']=1;$now=1790035200;
$store->query('DELETE FROM telemetry_events WHERE universe=1 AND actor=950');
$store->query('DELETE FROM telemetry_daily WHERE universe=1 AND actor=950 AND day>=?', [gmdate('Y-m-d',$now)]);
$store->write([event(950,$now,'fleet.send')],[1=>$s]);
$store->db->beginTransaction();
$store->query('SELECT * FROM telemetry_daily WHERE universe=1 AND actor=950 AND day=? FOR UPDATE',[gmdate('Y-m-d',$now)])->fetchAll();
$processes=[];
foreach ([$now+300,$now+900] as $at) {
    $processes[]=proc_open([PHP_BINARY,__DIR__.'/worker.php','write',(string)$at],[0=>['pipe','r'],1=>['file','/tmp/telemetry-worker.log','a'],2=>['file','/tmp/telemetry-worker.log','a']],$pipes);
}
usleep(150000);
$store->db->commit();
foreach ($processes as $process) {check(proc_close($process)===0,'concurrent writer completed');}
$row=$store->daily(1,950,$now)[0];
check(TelemetryActivity::windows([$row])===[[$now,$now+300],[$now+900,$now+900]],'concurrent activity windows survive');
check((int)$store->query('SELECT COUNT(*) FROM telemetry_events WHERE universe=1 AND actor=950')->fetchColumn()===3,'concurrent detailed events survive');
// A short health lock waits; a prolonged lock fails visibly within the request budget.
foreach ([20000, 200000] as $delay) {
    @unlink('/tmp/telemetry-health-ready');
    $handle=fopen(CACHE_PATH.'telemetry/health.json','c+');
    flock($handle,LOCK_EX);
    $process=proc_open([PHP_BINARY,__DIR__.'/worker.php','health',(string)($now+$delay)],
        [1=>['file','/tmp/telemetry-worker.log','a'],2=>['file','/tmp/telemetry-worker.log','a']],$pipes);
    $deadline=microtime(true)+3;
    while (!is_file('/tmp/telemetry-health-ready') && microtime(true)<$deadline) { usleep(1000); }
    check(is_file('/tmp/telemetry-health-ready'),'health writer ready');
    usleep($delay);
    flock($handle,LOCK_UN);fclose($handle);
    $exit=proc_close($process);
    check($delay===20000 ? $exit===0 : $exit!==0,'health contention has explicit outcome');
}
check((int)$store->query('SELECT COUNT(*) FROM telemetry_events WHERE universe=1 AND actor=950')->fetchColumn()===4,'brief contention preserves detailed event; timeout writes nothing');
$health=TelemetryStore::health();
check(isset($health['gap_start']) && $health['failure']==='health_unavailable','health contention records interruption');
$store->write([event(950,$now+1800,'fleet.send')],[1=>$s]);
TelemetryStore::health(['success'=>time()]);
check(in_array('health_unavailable',array_column(TelemetryStore::health()['gaps'],'reason')),'recovery retains health interruption');
// Exercise the real installer route against a version-7 game, including partial DDL failure.
$db=Database::get();
$original=file_get_contents(ROOT_PATH.'includes/config.php');
$settings=$db->selectSingle('SELECT telemetry_settings FROM %%CONFIG%% WHERE uni=1',[],'telemetry_settings');
$db->nativeQuery('ALTER TABLE %%CONFIG%% DROP COLUMN telemetry_settings');
$db->nativeQuery("DELETE FROM %%CRONJOBS%% WHERE class='TelemetryCronjob'");
$db->update('UPDATE %%SYSTEM%% SET dbVersion=7');
$upgrade=static function(): string {
    $context=stream_context_create(['http'=>['method'=>'POST','timeout'=>20,'ignore_errors'=>true]]);
    $body=file_get_contents('http://localhost/install/index.php?mode=doupgrade',false,$context);
    check($body!==false,'installer responds');
    return $body;
};
$upgrade();
check((int)$db->selectSingle('SELECT dbVersion FROM %%SYSTEM%%',[],'dbVersion')===7,'locked installer cannot upgrade');
touch(ROOT_PATH.'includes/ENABLE_INSTALL_TOOL');
chmod(ROOT_PATH.'includes',0777);
try {
    $broken=str_replace("'databasename' => 'telemetry_test'","'databasename' => 'missing_telemetry_test'",$original);
    check($broken!==$original,'fault changes only telemetry database');
    file_put_contents(ROOT_PATH.'includes/config.php',$broken);
    $failed=$upgrade();
    check(str_contains($failed,'missing_telemetry_test'),'installer reports dedicated database failure');
    check((int)$db->selectSingle('SELECT dbVersion FROM %%SYSTEM%%',[],'dbVersion')===7,'failed migration version unchanged');
    check((bool)$db->nativeQuery("SHOW COLUMNS FROM %%CONFIG%% LIKE 'telemetry_settings'"),'partial DDL completed before failure');
} finally {file_put_contents(ROOT_PATH.'includes/config.php',$original);}
$body=$upgrade();
check((int)$db->selectSingle('SELECT dbVersion FROM %%SYSTEM%%',[],'dbVersion')===8,'migration retry advances version');
check(!is_file(ROOT_PATH.'includes/ENABLE_INSTALL_TOOL'),'successful upgrade locks installer');
check((int)$db->selectSingle("SELECT COUNT(*) AS n FROM %%CRONJOBS%% WHERE class='TelemetryCronjob'",[],'n')===1,'one telemetry cleanup job');
check(!$db->nativeQuery("SHOW TABLES LIKE 'telemetry_daily'"),'telemetry table is not in game database');
check((bool)glob(ROOT_PATH.'includes/backups/2MoonsBackup_*.sql'),'installer retains game backup');
$db->update('UPDATE %%CONFIG%% SET telemetry_settings=:settings WHERE uni=1',[':settings'=>$settings]);
// A locked telemetry row has a one-second database wait, not an unbounded gameplay lock.
$lock=TelemetryConnection::open();$lock->beginTransaction();
$lock->query("SELECT * FROM telemetry_daily WHERE universe=1 AND actor=950 FOR UPDATE")->fetchAll();
$started=microtime(true);
try {$store->write([event(950,$now+1200,'fleet.send')],[1=>$s]);check(false,'locked row must time out');}
catch (PDOException $expected) {check(microtime(true)-$started<2.5,'telemetry row timeout bounded');}
$lock->rollBack();
$store=new TelemetryStore(TelemetryConnection::open());
$store->write([event(950,$now+1200,'fleet.send')],[1=>$s]);
check(in_array([$now+900,$now+1200],TelemetryActivity::windows($store->daily(1,950,$now)),true),'recovery after locked row');
echo "Concurrent MySQL writes, migration routing/failure/retry and lock timeout passed.\n";
