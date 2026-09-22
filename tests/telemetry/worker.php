<?php
require __DIR__.'/bootstrap.php';
$op=$argv[1]??'write';
if ($op==='blackhole') {
    $socket=stream_socket_server('tcp://0.0.0.0:13309',$errno,$error);
    check((bool)$socket,'blackhole socket');
    file_put_contents('/tmp/telemetry-blackhole-ready','1');
    $end=time()+30;
    while(time()<$end) {
        $client=@stream_socket_accept($socket,1);
        if ($client) {sleep(3);fclose($client);}
    }
} elseif ($op==='lock') {
    $pdo=TelemetryConnection::open();
    $pdo->beginTransaction();
    $pdo->query("SELECT * FROM telemetry_daily WHERE universe=1 AND actor=1 AND day=UTC_DATE() FOR UPDATE")->fetchAll();
    file_put_contents('/tmp/telemetry-lock-ready','1');
    sleep(20);$pdo->rollBack();
} else {
    if ($op==='health') { file_put_contents('/tmp/telemetry-health-ready','1'); }
    $s=TelemetrySettings::defaults();$s['enabled']=1;
    (new TelemetryStore(TelemetryConnection::open()))->write([event(950,(int)$argv[2],'fleet.send')],[1=>$s]);
}
