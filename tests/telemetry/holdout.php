<?php
require __DIR__.'/bootstrap.php';
$s=TelemetrySettings::defaults();$now=1790000000;
$report=[];
// These scenarios are separate from the fixture used to exercise individual rules.
foreach ([71,4071] as $identity) {
    $rows=[];$at=$now-30000;
    $delays=[23,173,11,61,317,37,109,47,211,19,79];
    for ($i=0;$i<11;++$i) {
        foreach (['galaxy.view','fleet.send','fleet.recall','queue.shipyard'] as $j=>$kind) {
            $rows[]=event($identity,$at,$kind,$identity+1);
            $at+=$delays[($i*4+$j)%count($delays)];
        }
        if ($i%3===1) {$rows[]=event($identity,$at++,'account.settings');}
        if ($i===5) {$at+=2700;}
    }
    $found=kinds(TelemetryDetectors::automation($rows,$s,$now));
    $report[]=['scenario'=>'held-out varied workflow','identity'=>$identity,'warnings'=>$found,
        'misses'=>array_values(array_diff(['workflow'],$found)),'false_positives'=>array_values(array_diff($found,['workflow']))];
    $human=[];$at=$now-20000;
    foreach ([41,123,17,600,219,13,810,233,108,11,47,600,231,81,171,751,92,620,15,244,189,842,28,166,728,164,48,926,329,61,700] as $delay) {
        $human[]=event($identity,$at,'fleet.send',$identity+1);$at+=$delay;
    }
    $found=kinds(TelemetryDetectors::automation($human,$s,$now));
    $report[]=['scenario'=>'held-out irregular manual farming','identity'=>$identity,'warnings'=>$found,'misses'=>[],'false_positives'=>$found];
    $transfer=event($identity,$now-200000,'delivery',$identity+1,['metal'=>3200000,'crystal'=>1200000]);
    $repayment=event($identity+1,$now-20,'delivery',$identity,['deuterium'=>1200000]);
    $found=TelemetryDetectors::pushing([$transfer,$repayment],$identity,$identity+1,[],$s,$now);
    $report[]=['scenario'=>'held-out mixed-resource legitimate exchange','identity'=>$identity,
        'warnings'=>kinds($found),'misses'=>[],'false_positives'=>kinds($found)];
}
foreach ($report as $row) {
    check(!$row['misses'] && !$row['false_positives'],$row['scenario'].' expected detection');
}
for ($i=0;$i<3;++$i) {
    check($report[$i]['warnings']===$report[$i+3]['warnings'],'equivalent account identities have equivalent outcomes');
}
file_put_contents(__DIR__.'/results/holdout.json',json_encode($report,JSON_PRETTY_PRINT));
echo "Six held-out evaluations: no misses or false positives; equivalent identities agree.\n";
