<?php

require_once realpath('./scripts/bootstrap.php');

$neo4j = $container['neo4j'];
$stack = $neo4j->stack();

echo 'Creating constraints and indexes.' . PHP_EOL;

$constraints = [
    ['Answer' => 'hash'],
    ['Group' => 'id'],
    ['Person' => 'token'],
    ['Question' => 'id'],
    ['Question' => 'order'],
    ['Survey' => 'id'],
];

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

// Get json_decode()-ed OSMI data from filesystem
$paths = glob(APPLICATION_PATH . '/data/*.json');
$decoded = array_map(function ($path) {
    return json_decode(file_get_contents($path), true);
}, $paths);

echo sprintf('Found %d files to process.', count($decoded)) . PHP_EOL;
$index = 0;

foreach ($decoded as $data) {
    echo sprintf("Importing data from '%s'.", $paths[$index])  . PHP_EOL;

    $stack = $neo4j->stack();
    $questions = [];
    $groups = [];

    $order = 0;

    foreach ($data['questions'] as $question) {
        // Skip statements
        if (strpos($question['id'], 'statement') !== false) {
            continue;
        }

        $question['question'] = strip_tags($question['question']);

        // Groups are their own nodes
        if (strpos($question['id'], 'group') !== false) {
            $cql = <<<GROUP
MERGE (g:Group { id: { id }})
ON CREATE SET
    g.description = { question },
    g.field_id = { field_id }
GROUP;
            $stack->push($cql, $question);

            continue;
        }

        if (!isset($question['group'])) {
            $question['group'] = null;
        }

        $cql = <<<QUESTION
MERGE (q:Question { id: { id }})
ON CREATE SET
    q.question = { question },
    q.field_id = { field_id },
    q.group = { group },
    q.order = { order }
WITH q
MATCH (g:Group { id: { group }})
MERGE (g)-[:CONTAINS]->(q);
QUESTION;

        $question['order'] = $order;
        $stack->push($cql, $question);
        $order++;
    }

    $neo4j->runStack($stack);
    $stack = $neo4j->stack();

    $respondents = [];

    foreach ($data['responses'] as $response) {
        $metadata = array_map(function (&$value) {
            if (strlen(trim($value)) === 0) {
                return;
            }

            return $value;
        }, $response['metadata']);

        if (isset($metadata['date_land'])) {
            // Convert to Java timestamp
            $metadata['date_land'] = (int) strtotime($metadata['date_land']) * 1000;
        }
        if (isset($metadata['date_submit'])) {
            // Convert to Java timestamp
            $metadata['date_submit'] = (int) strtotime($metadata['date_submit']) * 1000;
        }

        $params = ['token' => $response['token'], 'props' => $metadata];

        $cql = <<<PERSON
MERGE (p:Person { token: { token }})
ON CREATE SET p += { props }
PERSON;

        $stack->push($cql, $params);

        foreach ($response['answers'] as $questionId => $answer) {
            if (strlen(trim($answer)) === 0) {
                continue;
            }

            // This hash allows for a unique ID for Answer nodes
            $hash = md5($questionId . $answer);
            $params = [
                'hash' => $hash,
                'questionId' => $questionId,
                'answer' => $answer,
                'token' => $response['token'],
            ];

            $cql = <<<ANSWER
MERGE (a:Answer { hash: { hash }})
ON CREATE SET a.answer = { answer }
WITH a
MATCH (q:Question { id: { questionId }})
MATCH (p:Person { token: { token }})
MERGE (q)-[:HAS_ANSWER]->(a)
MERGE (p)-[:ANSWERED]->(a)
ANSWER;

            $stack->push($cql, $params);
        }
    }

    $results = $neo4j->runStack($stack);
}

echo 'Finished!' . PHP_EOL;
