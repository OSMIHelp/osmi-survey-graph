<?php

require_once realpath('./scripts/bootstrap.php');

if ($argc === 2 && $argv[1] === 'sleep') {
    echo 'Sleeping to allow Neo4j 3.0 time to start.';
    sleep(6);
}

$importRepo = $container['jsonImportRepository'];

try {
    \PHP_Timer::start();
    $paths = glob(APPLICATION_PATH . '/data/*.json');
    $decoded = array_map(function ($path) {
        return json_decode(file_get_contents($path), true);
    }, $paths);
    $time = \PHP_Timer::stop();
    echo 'Loaded data files to memory in ' . \PHP_Timer::secondsToTimeString($time) . PHP_EOL;

    echo sprintf('Found %d files to process.', count($decoded)) . PHP_EOL;
    $index = 0;

    foreach ($decoded as $data) {
        echo sprintf("Importing data from '%s'.", $paths[$index])  . PHP_EOL;
        \PHP_Timer::start();
        $importRepo->import($data);
        $time = \PHP_Timer::stop();
        echo sprintf("Imported data from '%s' in %s.", $paths[$index], \PHP_Timer::secondsToTimeString($time))  . PHP_EOL;
        $index++;
    }

    echo 'Finished importing data.' . PHP_EOL;
} catch (\Exception $e) {
    echo 'Exception importing data.' . PHP_EOL;
    echo $e->getMessage() . PHP_EOL;
    exit(1);
}

$extractDataRepo = $container['extractDataRepository'];

try {
    $extractDataRepo->extractData();

    echo 'Finished extracting data.' . PHP_EOL;
} catch (\Exception $e) {
    echo 'Exception extracting survey data.' . PHP_EOL;
    echo $e->getMessage() . PHP_EOL;
    exit(1);
}

echo 'Successfully completed load.' . PHP_EOL;
echo PHP_Timer::resourceUsage() . PHP_EOL;
