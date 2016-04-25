<?php

require_once realpath('./scripts/bootstrap.php');

if ($argc === 2 && $argv[1] === 'sleep') {
    echo 'Sleeping to allow Neo4j 3.0 time to start.';
    sleep(6);
}

echo 'Creating constraints and indexes.' . PHP_EOL;

$constraints = [
    ['Answer' => 'hash'],
    ['Country' => 'name'],
    ['Disorder' => 'name'],
    ['Group' => 'id'],
    ['Person' => 'token'],
    ['Planet' => 'name'],
    ['Profession' => 'name'],
    ['Question' => 'id'],
    ['Question' => 'order'],
    ['State' => 'name'],
    ['Survey' => 'year'],
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
        ['Question' => 'question'],
    ];

    foreach ($indexes as $index) {
        foreach ($index as $label => $property) {
            $stack->push(sprintf('CREATE INDEX ON :%s(%s)', $label, $property));
        }
    }

    \PHP_Timer::start();
    // Schema updates must be run first
    $neo4j->runStack($stack);
    $time = \PHP_Timer::stop();
    echo 'Constraints created in ' . \PHP_Timer::secondsToTimeString($time) . PHP_EOL;
} catch (\Exception $e) {
    echo 'Exception creating constraints and indexes.' . PHP_EOL;
    echo $e->getMessage() . PHP_EOL;
    exit(1);
}

echo 'Successfully completed creating constraints and indexes.' . PHP_EOL;
echo PHP_Timer::resourceUsage() . PHP_EOL;
