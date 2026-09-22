<?php
// This HTTP fixture is inert outside the dedicated test deployment.
if (getenv('TELEMETRY_RELEASE_TEST') !== '1') {
    http_response_code(404);
    exit;
}
require __DIR__.'/bootstrap.php';
$db=Database::get();
$store=new TelemetryStore(new PDO('mysql:host=mysql;dbname=telemetry_test','root',getenv('TELEMETRY_TEST_PASSWORD'),
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]));
$op=$_GET['op']??'state';
if ($op==='state') {
    $out=['users'=>$db->select('SELECT id,username,id_planet,authlevel,bana,b_tech_queue FROM %%USERS%% WHERE id<=50 ORDER BY id'),
        'planets'=>$db->select('SELECT id,id_owner,metal,crystal,deuterium,metal_mine,b_building_id,small_ship_cargo,spy_sonde FROM %%PLANETS%% WHERE id_owner<=50 ORDER BY id'),
        'events'=>$store->query('SELECT actor,target,kind,at,result,fleet_id,ip,request_id,data FROM telemetry_events WHERE universe=1 AND actor<=50 ORDER BY id LIMIT 5000')->fetchAll(PDO::FETCH_ASSOC),
        'warnings'=>$store->query('SELECT id,actor,other,kind,status,latest_evidence,evidence FROM telemetry_warnings WHERE universe=1 ORDER BY id LIMIT 1000')->fetchAll(PDO::FETCH_ASSOC),
        'fleets'=>$db->select('SELECT fleet_id,fleet_owner,fleet_target_owner,fleet_resource_metal,fleet_mess FROM %%FLEETS%% WHERE fleet_owner<=50 ORDER BY fleet_id'),
        'activity'=>$store->daily(1,1,strtotime('today UTC')),
        'health'=>TelemetryStore::health()];
} elseif (str_starts_with($op,'fault_')) {
    $mode=substr($op,6);
    $original=file_get_contents(ROOT_PATH.'includes/config.test.php');
    file_put_contents(ROOT_PATH.'includes/config.php',$original);
    if (is_file('/tmp/telemetry-fault-pid')) {
        $pid=(int)file_get_contents('/tmp/telemetry-fault-pid');
        if ($pid>1) {exec('kill '.(int)$pid);}
        unlink('/tmp/telemetry-fault-pid');
    }
    $settings=TelemetrySettings::defaults();
    $config=Config::get(1);$modules=array_pad(explode(';',$config->moduls),MODULE_AMOUNT,1);
    $modules[MODULE_TELEMETRY]=$mode==='disabled'?0:1;$config->moduls=implode(';',$modules);
    Config::get(1)->telemetry_settings=json_encode($settings);Config::get(1)->save();
    require ROOT_PATH.'includes/config.test.php';
    if ($mode==='refused') {$telemetry['host']='127.0.0.1';$telemetry['port']=9;}
    if ($mode==='permission') {$telemetry['user']='read_only';$telemetry['userpw']=getenv('TELEMETRY_TEST_PASSWORD');}
    if ($mode==='timeout' || $mode==='locked') {
        $worker=$mode==='timeout'?'blackhole':'lock';
        $ready='/tmp/telemetry-'.($worker==='lock'?'lock':'blackhole').'-ready';
        @unlink($ready);
        exec('/usr/local/bin/php '.escapeshellarg(__DIR__.'/worker.php').' '.$worker.' >/tmp/telemetry-fault.log 2>&1 & echo $!', $pids);
        file_put_contents('/tmp/telemetry-fault-pid',(string)end($pids));
        $until=microtime(true)+3;
        while(!is_file($ready) && microtime(true)<$until) {usleep(10000);}
        check(is_file($ready),'fault worker ready');
        if ($mode==='timeout') {$telemetry['host']='127.0.0.1';$telemetry['port']=13309;}
    }
    file_put_contents(ROOT_PATH.'includes/config.php',"<?php\n".'$database='.var_export($database,true).";\n"
        .'$salt='.var_export($salt,true).";\n".'$telemetry='.var_export($telemetry,true).";\n");
    if (function_exists('opcache_invalidate')) {
        opcache_invalidate(ROOT_PATH.'includes/config.php',true);
    }
    $out=['fault'=>$mode];
} elseif ($op==='sql_count') {
    $out=['telemetry_queries'=>(int)$db->getHandle()->query("SELECT COUNT(*) FROM mysql.general_log
        WHERE command_type='Query' AND argument LIKE 'SET SESSION innodb_lock_wait_timeout=%'")->fetchColumn()];
} elseif ($op==='clear_activity') {
    $store->query('DELETE FROM telemetry_daily WHERE universe=1 AND actor=1 AND day=UTC_DATE()');
    $out=['cleared'=>true];
} elseif ($op==='workflow') {
    $s=TelemetrySettings::get(1);$rows=[];$at=time()-86400;
    for ($i=0;$i<15;++$i) {
        foreach (['galaxy.view','fleet.send','queue.buildings'] as $kind) {
            $rows[]=event(1,$at,$kind,2);$at+=30;
        }
    }
    $store->write($rows,[1=>$s]);$out=['seeded'=>count($rows)];
} elseif ($op==='workflow_remove') {
    $store->query('DELETE FROM telemetry_events WHERE universe=1 AND actor=1 AND at<?',[time()-3600]);
    $out=['removed'=>true];
} elseif ($op==='rollback_on' || $op==='rollback_off') {
    $db->nativeQuery('DROP TRIGGER IF EXISTS telemetry_test_rollback');
    if ($op==='rollback_on') {
        $db->nativeQuery("CREATE TRIGGER telemetry_test_rollback BEFORE UPDATE ON %%USERS%% FOR EACH ROW
            BEGIN IF NEW.id=1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='synthetic persistence failure'; END IF; END");
    }
    $out=['trigger'=>$op];
} elseif ($op==='deliver') {
    require ROOT_PATH.'includes/vars.php';
    require_once ROOT_PATH.'includes/classes/class.MissionFunctions.php';
    require_once ROOT_PATH.'includes/classes/missions/Mission.interface.php';
    require_once ROOT_PATH.'includes/classes/missions/MissionCaseTransport.class.php';
    $fleet=$db->selectSingle('SELECT * FROM %%FLEETS%% WHERE fleet_owner=1 AND fleet_mission=3 AND fleet_mess=0 ORDER BY fleet_id DESC LIMIT 1');
    check((bool)$fleet,'transport fixture exists');
    $db->beginTransaction();
    $mission=new MissionCaseTransport($fleet);$mission->TargetEvent();
    $db->commit();PlayerTelemetry::flush();$out=['delivered'=>$fleet['fleet_id']];
} elseif ($op==='network_off' || $op==='network_on') {
    $settings=TelemetrySettings::get(1);$settings['network_enabled']=$op==='network_on'?1:0;
    Config::get(1)->telemetry_settings=json_encode($settings);Config::get(1)->save();
    $out=['network_enabled'=>$settings['network_enabled']];
} elseif ($op==='escaped_fields') {
    $payload='<img src=x onerror=txss=1>';
    $db->update('UPDATE %%USERS%% SET username=:name WHERE id=2',[':name'=>$payload]);
    $s=TelemetrySettings::get(1);$now=time();
    $store->write([event(2,$now,'interaction',0,['client'=>$payload])],[1=>$s]);
    $store->warning(1,1,2,['kind'=>'pushing.2','strength'=>'moderate','explanation'=>$payload,
        'from'=>$now-60,'to'=>$now,'metrics'=>[],'timeline'=>[]],$s,$now);
    $out=['payload'=>$payload];
} elseif ($op==='permissions') {
    $USER=['authlevel'=>AUTH_MOD,'rights'=>[]];
    check(!allowedTo('ShowTelemetryPage'),'moderator without permission denied');
    $USER['rights']['ShowTelemetryPage']=1;
    check(allowedTo('ShowTelemetryPage'),'moderator with permission admitted');
    $USER=['authlevel'=>AUTH_ADM,'rights'=>[]];
    check(allowedTo('ShowTelemetryPage'),'admin admitted');
    $out=['checked'=>true];
} elseif (in_array($op,['moderator_on','moderator_grant','moderator_off'],true)) {
    $rights=$op==='moderator_grant'?['ShowTelemetryPage'=>1]:[];
    $db->update('UPDATE %%USERS%% SET authlevel=:level,rights=:rights WHERE id=2',
        [':level'=>$op==='moderator_off'?AUTH_USR:AUTH_MOD,':rights'=>serialize($rights)]);
    $out=['updated'=>true];
} else {
    throw new InvalidArgumentException('Unknown fixture operation.');
}
header('Content-Type: application/json');
echo json_encode($out,JSON_THROW_ON_ERROR);
