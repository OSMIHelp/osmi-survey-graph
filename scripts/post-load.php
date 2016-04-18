<?php

require_once realpath('./scripts/bootstrap.php');

use GraphAware\Neo4j\Client\Exception\Neo4jException;

$container['extractDataRepository']->extractData();
$neo4j = $container['neo4j'];

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
