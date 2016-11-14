#!/usr/bin/env php
<?php

require_once (dirname(__FILE__) . '/bootstrap.php');

use League\Csv\Writer;

/** @var GraphAware\Neo4j\Client\Client $neo4j */
$neo4j = $container['neo4j'];


/**
 * GET QUESTIONS
 */
$cql = <<<EOT
MATCH (q:Question)
RETURN q.question as question, q.field_id as question_id, q.order as order
ORDER BY q.order;
EOT;

$questions = [];
$result = $neo4j->run($cql);
foreach ($result->records() as $record) {
    $q = [
        'question' => $record->get('question'),
        'question_id' => $record->get('question_id'),
        'order' => $record->get('order'),
    ];
    $questions[] = $q;
}

/**
 * make questions row
 */
$questionsRow = [];
foreach ($questions as $question) {
    $questionsRow[$question['question_id']] = $question['question'];
}

/**
 * GET PERSONS
 */
$cql = <<<EOT
MATCH (p:Person)
RETURN p.token as person_id, p.date_submit as date_submit
ORDER BY date_submit
EOT;

$persons = [];
$result = $neo4j->run($cql);
foreach ($result->records() as $record) {
    $p = [
        'person_id' => $record->get('person_id'),
        'date_submit' => $record->get('date_submit'),
    ];
    $persons[$p['person_id']] = $p;
}

/**
 * GET ANSWERS PER PERSON
 */
$answerSets = [];
foreach ($persons as $person) {
    $cql = <<<EOT
MATCH (p:Person { token: { person_id } })-[:ANSWERED]->(a:Answer)<-[:HAS_ANSWER]-(q:Question)
RETURN a.answer as answer, q.question as question, q.field_id as question_id, p.network_id as person_id, a.hash as answer_id

EOT;
    try {
        $result = $neo4j->run($cql, ['person_id' => $person['person_id']]);
    } catch (Exception $e) {
        die($e->getMessage());
    }
    $answerSet = [];
    foreach ($result->records() as $record) {
        $a = [
            'answer' => $record->get('answer'),
            'question' => $record->get('question'),
            'question_id' => $record->get('question_id'),
            'person_id' => $record->get('person_id'),
            'answer_id' => $record->get('answer_id'),
        ];
        if (empty($answerSet[$a['question_id']])) {
            $answerSet[$a['question_id']] = $a;
        } else {
            $answerSet[$a['question_id']]['answer'] .= '|' . $a['answer'];
        }

    }
    $answerSets[] = $answerSet;
}

/**
 * build answer rows
 */
$answerRows = [];
foreach ($answerSets as $answerSet) {
    $answerRowSet = [];
    foreach ($questions as $question) {
        if (empty($answerSet[$question['question_id']])) {
            $answerRowSet[$question['question_id']] = null;
        } else {
            $answerRowSet[$question['question_id']] = $answerSet[$question['question_id']]['answer'];
        }
    }
    $answerRows[] = $answerRowSet;
}

/**
 * build CSV
 */
$csv = Writer::createFromFileObject(new SplTempFileObject());
$csv->insertOne($questionsRow);
$csv->insertAll($answerRows);
$csv->output();
