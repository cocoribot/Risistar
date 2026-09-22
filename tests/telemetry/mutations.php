<?php
require __DIR__.'/bootstrap.php';
$mutants=[
    ['includes/classes/Database.class.php',
        'unset($this->commitCallbacks[$this->transactionDepth]);',
        'foreach ($this->commitCallbacks[$this->transactionDepth] ?? [] as $callback) { $callback(); } unset($this->commitCallbacks[$this->transactionDepth]);',
        'run.php','rolled back savepoint'],
    ['includes/classes/TelemetryStore.class.php',
        "array_merge(\$previous, \$d['windows'])", "\$d['windows']", 'database.php','concurrent activity windows'],
    ['includes/classes/PlayerTelemetry.class.php',
        "&& (\$query['passive_reload'] ?? '') === 'queue'", '&& false', 'run.php','automatic building reload'],
    ['includes/classes/TelemetryDetectors.class.php',
        "(1 - \$s['imbalance_allowance']) *", '(1 - 0) *', 'run.php','25 percent tolerance'],
];
$results=[];
foreach ($mutants as [$file,$before,$after,$script,$expected]) {
    $path=ROOT_PATH.$file;$original=file_get_contents($path);
    check(str_contains($original,$before),'mutation target exists: '.$file);
    try {
        file_put_contents($path,str_replace($before,$after,$original));
        $process=proc_open([PHP_BINARY,__DIR__.'/'.$script],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
        $output=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
        $exit=proc_close($process);
        check($exit!==0 && str_contains($output,$expected),'mutant caught by its intended assertion: '.$file.' '.$output);
        $results[]=['file'=>$file,'assertion'=>$expected,'failed_as_expected'=>true];
    } finally {file_put_contents($path,$original);}
}
foreach (['run.php','database.php'] as $script) {
    $process=proc_open([PHP_BINARY,__DIR__.'/'.$script],[1=>STDOUT,2=>STDERR],$pipes);
    check(proc_close($process)===0,'corrected code passes '.$script);
}
file_put_contents(__DIR__.'/results/mutations.json',json_encode($results,JSON_PRETTY_PRINT));
echo "Four representative mutants rejected; corrected code passed.\n";
