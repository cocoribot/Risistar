<?php
if (PHP_SAPI !== 'cli' || getenv('TELEMETRY_RELEASE_TEST') !== '1') {
    exit(1);
}
register_shutdown_function(static function() {
    if (!defined('TELEMETRY_BOOTSTRAPPED')) { exit(1); }
});
chdir(dirname(__DIR__,2));
$password=getenv('TELEMETRY_TEST_PASSWORD');
if (!$password) { throw new RuntimeException('Missing isolated test password.'); }
$pdo=new PDO('mysql:host=mysql;port=3306','root',$password,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
foreach (['risistar_test','telemetry_test'] as $name) {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $name CHARACTER SET utf8mb4");
}
$quotedPassword=$pdo->quote($password);
$pdo->exec("CREATE USER IF NOT EXISTS 'collector'@'%' IDENTIFIED BY $quotedPassword");
$pdo->exec("GRANT SELECT,INSERT,UPDATE,DELETE ON telemetry_test.* TO 'collector'@'%'");
$pdo->exec("CREATE USER IF NOT EXISTS 'read_only'@'%' IDENTIFIED BY $quotedPassword");
$pdo->exec("GRANT SELECT ON telemetry_test.* TO 'read_only'@'%'");
$database=['host'=>'mysql','port'=>3306,'user'=>'root','userpw'=>$password,'databasename'=>'risistar_test','tableprefix'=>'uni1_'];
$salt=substr(str_replace('+','.',base64_encode(random_bytes(18))),0,22);
$telemetry=['enabled'=>true,'host'=>'mysql','port'=>3306,'databasename'=>'telemetry_test','user'=>'collector','userpw'=>$password];
$config="<?php\n".'$database='.var_export($database,true).";\n".'$salt='.var_export($salt,true).";\n"
    .'$telemetry='.var_export($telemetry,true).";\n";
file_put_contents('includes/config.php',$config);
file_put_contents('includes/config.test.php',$config);
file_put_contents('telemetry-test-fixture.php',"<?php require __DIR__.'/tests/telemetry/fixture.php';");
@mkdir('cache',0777,true);
@mkdir('cache/sessions',0777,true);
@mkdir('cache/templates',0777,true);
@mkdir('cache/telemetry',0777,true);
chmod('cache/telemetry',0777);
file_put_contents('cache/telemetry/health.json','{"gaps":[]}');
chmod('cache/telemetry/health.json',0666);
chmod('includes/config.php',0666);
@mkdir('includes/backups',0777,true);chmod('includes/backups',0777);
chmod('cache',0777);chmod('cache/sessions',0777);chmod('cache/templates',0777);
define('ROOT_PATH',getcwd().'/');
define('MODE','INSTALL');
require 'includes/common.php';
restore_exception_handler();
define('TELEMETRY_BOOTSTRAPPED',true);
$db=Database::get();
$db->getHandle()->exec('DROP DATABASE risistar_test');
$db->getHandle()->exec('CREATE DATABASE risistar_test CHARACTER SET utf8mb4');
$db->getHandle()->exec('USE risistar_test');
$sql=str_replace(['%PREFIX%','%VERSION%','%REVISION%','%DB_VERSION%'],['uni1_','1.8.0','0','7'],file_get_contents('install/install.sql'));
$db->getHandle()->exec($sql);
$db->getHandle()->exec('SET autocommit=1');
$pdo->exec('DROP DATABASE telemetry_test');
$pdo->exec('CREATE DATABASE telemetry_test CHARACTER SET utf8mb4');
$pdo->exec('USE telemetry_test');
$pdo->exec(file_get_contents('install/telemetry.sql'));
$migration=require 'install/migrations/migration_8.php';
$migration($db);
$db->update('UPDATE %%SYSTEM%% SET dbVersion=8');
Config::reload();
$cfg=Config::get(1);
$settings=TelemetrySettings::defaults();$settings['enabled']=1;
$cfg->telemetry_settings=json_encode($settings);
$cfg->lang='fr';$cfg->timezone='UTC';$cfg->game_disable=1;
$cfg->save();
require 'includes/vars.php';
$LNG=new Language('fr');
$LNG->includeData(['L18N','INGAME','TECH']);
for ($i=1;$i<=50;++$i) {
    PlayerUtil::createPlayer(1,'Synthetic'.$i,PlayerUtil::cryptPassword($password),
        'synthetic'.$i.'@example.test','fr',1,10+$i,5,null,$i===1?AUTH_ADM:AUTH_USR);
}
$db->nativeQuery("UPDATE %%PLANETS%% SET metal=100000000,crystal=100000000,deuterium=100000000,
    small_ship_cargo=100,spy_sonde=100,robot_factory=5,hangar=5,laboratory=5,last_update=UNIX_TIMESTAMP()");
$db->nativeQuery("UPDATE %%USERS%% SET computer_tech=10,combustion_tech=5,impulse_motor_tech=5,urlaubs_modus=0,authattack=0");
$db->nativeQuery("UPDATE %%CRONJOBS%% SET isActive=0");
$pdo->exec("SET GLOBAL log_output='TABLE'");
$pdo->exec("SET GLOBAL general_log=ON");
@mkdir('tests/telemetry/results',0777,true);
echo "PHP ".PHP_VERSION."; MySQL ".$pdo->getAttribute(PDO::ATTR_SERVER_VERSION)."; 50 isolated synthetic accounts.\n";
