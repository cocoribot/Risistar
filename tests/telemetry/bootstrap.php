<?php
if (getenv('TELEMETRY_RELEASE_TEST') !== '1') {
    throw new RuntimeException('Use scripts/test-telemetry.sh; a real isolated MySQL database is required.');
}
register_shutdown_function(static function() {
    if (!defined('TELEMETRY_BOOTSTRAPPED')) { exit(1); }
});
chdir(dirname(__DIR__,2));
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH',getcwd().'/');
    define('MODE','INSTALL');
    require 'includes/common.php';
    restore_exception_handler();
    define('TELEMETRY_BOOTSTRAPPED',true);
}
require_once ROOT_PATH.'includes/classes/TelemetryDetectors.class.php';
require_once ROOT_PATH.'includes/classes/TelemetryReview.class.php';
require_once ROOT_PATH.'includes/classes/TelemetrySchema.class.php';
if (version_compare(PHP_VERSION,'8.4','<') || !str_starts_with(Database::get()->getHandle()->getAttribute(PDO::ATTR_SERVER_VERSION),'8.')) {
    throw new RuntimeException('Release checks require PHP 8.4 and MySQL 8.');
}
function check(bool $condition,string $message): void {
    if (!$condition) {
        throw new RuntimeException('FAIL: '.$message);
    }
}
function event(int $actor,int $at,string $kind,int $target=0,array $data=[],int $fleetId=0): array {
    static $id=0;
    ++$id;
    return ['id'=>$id,'event_key'=>'test-'.getmypid().'-'.$id,'request_id'=>str_pad((string)$id,32,'0',STR_PAD_LEFT),
        'universe'=>1,'actor'=>$actor,'target'=>$target,'at'=>$at,'kind'=>$kind,'data'=>$data,'fleet_id'=>$fleetId,
        'result'=>'success','interactive'=>!in_array($kind,['delivery','combat'],true),'ip'=>'192.0.2.10'];
}
function kinds(array $findings): array { return array_column($findings,'kind'); }
