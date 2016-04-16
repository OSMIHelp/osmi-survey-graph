<?php

require_once realpath('./scripts/bootstrap.php');

use GraphAware\Neo4j\Client\Exception\Neo4jException;

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

$stack = $neo4j->stack();

echo 'Creating country nodes from responses.' . PHP_EOL;
$cql = <<<COUNTRIES
MATCH (q:Question)-[:HAS_ANSWER]->(a)
WHERE q.question CONTAINS('What country do you')
WITH COLLECT(DISTINCT a.answer) AS countries
UNWIND countries AS country
MERGE (c:Country { name: country })
WITH c
MERGE (p:Planet { name: 'Earth' })
MERGE (p)-[:CHILD]->(c);
COUNTRIES;

$stack->push($cql);

echo 'Creating US state nodes from responses.' . PHP_EOL;
$cql = <<<STATES
MATCH (q:Question)-[:HAS_ANSWER]->(a)
WHERE q.question CONTAINS('What US state or territory')
WITH COLLECT(DISTINCT a.answer) AS states
UNWIND states AS state
MERGE (s:State { name: state })
WITH s
MATCH (c:Country { name: 'United States of America' })
MERGE (c)-[:CHILD]->(s);
STATES;

$stack->push($cql);

echo 'Match respondents to countries of residence, excepting the US.' . PHP_EOL;
$cql = <<<LIVES_IN_COUNTRY
MATCH (q:Question)-[:HAS_ANSWER]->(a)<-[:ANSWERED]-(p)
WHERE q.question CONTAINS('What country do you live in')
AND NOT a.answer = "United States of America"
WITH a.answer AS countryName, COLLECT(p) AS residents
MATCH (c:Country { name: countryName })
WITH c, residents
UNWIND residents AS resident
MERGE (resident)-[:LIVES_IN]->(c);
LIVES_IN_COUNTRY;

$stack->push($cql);

echo 'Match respondents to countries they work in, excepting the US.' . PHP_EOL;
$cql = <<<WORKS_IN_COUNTRY
MATCH (q:Question)-[:HAS_ANSWER]->(a)<-[:ANSWERED]-(p)
WHERE q.question CONTAINS('What country do you work in')
AND NOT a.answer = "United States of America"
WITH a.answer AS countryName, COLLECT(p) AS residents
MATCH (c:Country { name: countryName })
WITH c, residents
UNWIND residents AS resident
MERGE (resident)-[:WORKS_IN]->(c);
WORKS_IN_COUNTRY;

$stack->push($cql);

// What state do you live in?
$cql = "MATCH (q:Question)-[:HAS_ANSWER]->(a)<-[:ANSWERED]-(p)
WHERE q.question CONTAINS('state or territory do you live in')
WITH a.answer AS stateName, COLLECT(p) AS residents
MATCH (s:State { name: stateName })
WITH s, residents
UNWIND residents AS resident
MERGE (resident)-[:LIVES_IN]->(s)";

$stack->push($cql);

// What state do you work in?
$cql = "MATCH (q:Question)-[:HAS_ANSWER]->(a)<-[:ANSWERED]-(p)
WHERE q.question CONTAINS('state or territory do you work in')
WITH a.answer AS stateName, COLLECT(p) AS residents
MATCH (s:State { name: stateName })
WITH s, residents
UNWIND residents AS resident
MERGE (resident)-[:WORKS_IN]->(s)";

$stack->push($cql);

// What do you do?
$cql = "MATCH (q:Question { question: 'Which of the following best describes your work position?' })-[:HAS_ANSWER]->(a)<-[:ANSWERED]-(p)
WITH a.answer AS professionName, COLLECT(p) AS respondents
MERGE (profession:Profession { name: professionName })
WITH profession, respondents
UNWIND respondents AS respondent
MERGE (respondent)-[:WORKS_AS]->(profession)";

$stack->push($cql);
$neo4j->runStack($stack);
