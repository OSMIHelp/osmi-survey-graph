<?php

require_once realpath('./scripts/bootstrap.php');

echo 'Creating constraints and indexes.' . PHP_EOL;

$constraints = [
    ['Answer' => 'hash'],
    ['Country' => 'name'],
    ['Group' => 'id'],
    ['Person' => 'token'],
    ['Planet' => 'name'],
    ['Profession' => 'name'],
    ['Question' => 'id'],
    ['Question' => 'order'],
    ['State' => 'name'],
    ['Survey' => 'id'],
];

$neo4j = $container['neo4j'];

try {
    $stack = $neo4j->stack();

    foreach ($constraints as $constraint) {
        foreach ($constraint as $label => $property) {
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
    ];

    foreach ($indexes as $index) {
        foreach ($index as $label => $property) {
            $stack->push(sprintf('CREATE INDEX ON :%s(%s)', $label, $property));
        }
    }

    // Schema updates must be run first
    $neo4j->runStack($stack);

    echo 'Created constraints and indexes.' . PHP_EOL;
} catch (\Exception $e) {
    echo $e->getMessage() . PHP_EOL;
    exit(1);
}

try {
    $paths = glob(APPLICATION_PATH . '/data/*.json');
    $decoded = array_map(function ($path) {
        return json_decode(file_get_contents($path), true);
    }, $paths);

    echo sprintf('Found %d files to process.', count($decoded)) . PHP_EOL;
    $index = 0;

    foreach ($decoded as $data) {
        echo sprintf("Importing data from '%s'.", $paths[$index])  . PHP_EOL;
        $container['jsonImportRepository']->import($data);
        $index++;
    }
} catch (\Exception $e) {
    echo $e->getMessage() . PHP_EOL;
    exit(1);
}

echo 'Finished!' . PHP_EOL;
