<?php

namespace OSMI\Survey\Graph\Repository;

class ExtractData extends Neo4j
{
    public function extractData()
    {
        $this->client->run($this->getCreateSurvey());
        $this->client->run($this->getRemoveDuplicatedQuestions());
        $this->client->run($this->getCreateListOfQuestions());
        $this->client->run($this->getCreateSurveyLinkedList());
        $this->client->run($this->getCreateCountries());
        $this->client->run($this->getCreateStates());
    }

    public function getCreateSurvey()
    {
        return 'MERGE (s:Survey { id: 1 }) SET s.year = 2016';
    }

    public function getRemoveDuplicatedQuestions()
    {
        return <<<CQL
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
// collection but I'm tired and want to move on to something else.
MATCH (q:Question { field_id: field_id })-[:HAS_ANSWER]->(a)
WHERE NOT q :Keep
DETACH DELETE q
CREATE UNIQUE (keep)-[:HAS_ANSWER]->(a);
CQL;
    }

    public function getCreateListOfQuestions()
    {
        return <<<CQL
MATCH (q:Question)
WITH q
ORDER BY q.order
WITH COLLECT(q) AS questions
UNWIND RANGE(0,LENGTH(questions) - 2) as idx
WITH questions[idx] AS q1, questions[idx+1] AS q2
MERGE (q1)-[:QUESTION]->(q2)
CQL;
    }

    public function getCreateSurveyLinkedList()
    {
        return <<<CQL
MATCH (s:Survey { id: 1 })
MATCH (first:Question)
WHERE NOT (first)<-[:QUESTION]-()
MATCH (last:Question)
WHERE NOT (last)-[:QUESTION]->()
CREATE UNIQUE (last)-[:QUESTION]->(s)-[:QUESTION]->(first)
CQL;
    }

    public function getCreateCountries()
    {
        return <<<CQL
MATCH (q:Question)-[:HAS_ANSWER]->(a)
WHERE q.question CONTAINS('What country do you')
WITH COLLECT(DISTINCT a.answer) AS countries
UNWIND countries AS country
MERGE (c:Country { name: country })
WITH c
MERGE (p:Planet { name: 'Earth' })
MERGE (p)-[:CHILD]->(c);
CQL;
    }

    public function getCreateStates()
    {
        return <<<CQL
MATCH (q:Question)-[:HAS_ANSWER]->(a)
WHERE q.question CONTAINS('What US state or territory')
WITH COLLECT(DISTINCT a.answer) AS states
UNWIND states AS state
MERGE (s:State { name: state })
WITH s
MATCH (c:Country { name: 'United States of America' })
MERGE (c)-[:CHILD]->(s);
CQL;
    }
}
