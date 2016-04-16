<?php

require_once realpath('./scripts/bootstrap.php');

$cql = <<<DUPE_QUESTIONS
MATCH (q:Question)
WITH q.field_id as field_id, COUNT(q.field_id) AS dupes
WHERE dupes > 1
WITH COLLECT(field_id) AS field_ids
UNWIND field_ids AS field_id
WITH DISTINCT field_id
MATCH (q:Question { field_id: field_id })
WITH q, field_id
ORDER BY q.id
WITH COLLECT(q) AS questions, field_id
WITH HEAD(questions) AS keep, questions, field_id
SET keep :Keep
WITH keep, field_id
// This could be more efficient if I filtered out `keep` from the `questions`
// collection but I'm so tired of messing with this. Moving on to something else.
MATCH (q:Question { field_id: field_id })-[:HAS_ANSWER]->(a)
WHERE NOT q :Keep
DETACH DELETE q
CREATE UNIQUE (keep)-[:HAS_ANSWER]->(a);
DUPE_QUESTIONS;

echo 'Cleaning up duplicate questions.' . PHP_EOL;
$neo4j = $container['neo4j'];
$neo4j->run($cql);

echo 'Creating Survey node.' . PHP_EOL;
$neo4j->run('MERGE (s:Survey { id: 1 }) SET s.year = 2016');

echo 'Creating question list.' . PHP_EOL;
$cql = <<<QUESTION_LIST
MATCH (q:Question)
WITH q
ORDER BY q.order
WITH COLLECT(q) AS questions
UNWIND RANGE(0,LENGTH(questions) - 2) as idx 
WITH questions[idx] AS q1, questions[idx+1] AS q2
MERGE (q1)-[:QUESTION]->(q2)
QUESTION_LIST;

$neo4j->run($cql);

echo 'Creating linked list with survey and questions.' . PHP_EOL;
$cql = <<<LINKED_LIST
MATCH (s:Survey { id: 1 })
MATCH (first:Question)
WHERE NOT (first)<-[:QUESTION]-()
MATCH (last:Question)
WHERE NOT (last)-[:QUESTION]->()
CREATE UNIQUE (last)-[:QUESTION]->(s)-[:QUESTION]->(first)
LINKED_LIST;

$neo4j->run($cql);

echo 'Updating schema.' . PHP_EOL;
$label = 'Question';
$property = 'field_id';

try {
    $neo4j->run(sprintf('DROP INDEX ON :%s(%s)', $label, $property));
} catch (Neo4jException $e) {
    if (strpos($e->getMessage(), 'Index belongs to constraint') === false) {
        throw $e;
    }

    // If the index already belongs to a constraint, then this has been run
    // already and does not need to be run again. Ignore exception.
}

$neo4j->run(sprintf(
    'CREATE CONSTRAINT ON (n:%s) ASSERT n.%s IS UNIQUE',
    $label,
    $property
));
