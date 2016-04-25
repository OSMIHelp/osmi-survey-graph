<?php

require_once realpath('./scripts/bootstrap.php');

}

$importRepo = $container['jsonImportRepository'];

try {
    $paths = glob(APPLICATION_PATH . '/data/*.json');
    $decoded = array_map(function ($path) {
        return json_decode(file_get_contents($path), true);
    }, $paths);

    echo sprintf('Found %d files to process.', count($decoded)) . PHP_EOL;
    $index = 0;

    foreach ($decoded as $data) {
        echo sprintf("Importing data from '%s'.", $paths[$index])  . PHP_EOL;
        $importRepo->import($data);
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
