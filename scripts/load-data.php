#!/usr/bin/env php
<?php

require_once realpath('./scripts/bootstrap.php');

echo 'Creating constraints and indexes.' . PHP_EOL;

$constraints = [
    ['Answer' => 'hash'],
    ['Answer' => 'uuid'],
    ['Country' => 'name'],
    ['Country' => 'uuid'],
    ['Disorder' => 'name'],
    ['Disorder' => 'uuid'],
    ['Group' => 'id'],
    ['Group' => 'uuid'],
    ['Person' => 'token'],
    ['Person' => 'uuid'],
    ['Planet' => 'name'],
    ['Planet' => 'uuid'],
    ['Profession' => 'name'],
    ['Profession' => 'uuid'],
    ['Question' => 'id'],
    ['Question' => 'order'],
    ['Question' => 'uuid'],
    ['State' => 'name'],
    ['State' => 'uuid'],
    ['Survey' => 'id'],
    ['Survey' => 'uuid'],
];

$neo4j = $container['neo4j'];

try {
    $stack = $neo4j->stack();

    foreach ($constraints as $constraint) {
        foreach ($constraint as $label => $property) {
            echo "Adding constraint {$label}.{$property}." . PHP_EOL;
            $stack->push(sprintf(
                'CREATE CONSTRAINT ON (n:%s) ASSERT n.%s IS UNIQUE',
                $label,
                $property
            ));
        }
    }

    $indexes = [
        ['Question' => 'field_id'],
        ['Question' => 'group'],
        ['Question' => 'question'],
    ];

    foreach ($indexes as $index) {
        foreach ($index as $label => $property) {
            echo "Adding index {$label}.{$property}." . PHP_EOL;
            $stack->push(sprintf('CREATE INDEX ON :%s(%s)', $label, $property));
        }
    }

    // Schema updates must be run first
    echo 'Running stack' . PHP_EOL;
    $neo4j->runStack($stack);

    echo 'Created constraints and indexes.' . PHP_EOL;
} catch (\Exception $e) {
    echo 'Exception creating constraints and indexes.' . PHP_EOL;
    echo $e->getMessage() . PHP_EOL;
    exit(1);
}

/** @var \OSMI\Survey\Graph\Repository\JsonImport $importRepo */
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
    echo 'Extracting data.' . PHP_EOL;

    $extractDataRepo->extractData();

    echo 'Finished extracting data.' . PHP_EOL;
} catch (\Exception $e) {
    echo 'Exception extracting survey data.' . PHP_EOL;
    echo $e->getMessage() . PHP_EOL;
    exit(1);
}
