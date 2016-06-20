<?php

/**
 * OSMI Survey Graph project.
 *
 * @link https://github.com/OSMIHelp/osmi-survey-graph
 */
namespace OSMI\Survey\Graph\Repository;

use GraphAware\Neo4j\Client\Exception\Neo4jException;

/**
 * Creates a "graphy" model from the survey results, allowing for easier survey data analysis.
 *
 * This is probably a good start, and could definitely be fleshed out a little
 * more. If it's difficult to write a query for something because one has to
 * dig into the questions and answers, it might be good to add a new extraction
 * here.
 */
class ExtractData extends Neo4j
{
    /**
     * Performs the data extraction.
     */
    public function extractData()
    {
        $this->client->run($this->getCreateSurvey());
        $this->client->run($this->getRemoveDuplicatedQuestions());
        $this->updateQuestionFieldIdSchema();
        $this->client->run($this->getCreateListOfQuestions());
        $this->client->run($this->getCreateSurveyLinkedList());
        $this->client->run($this->getCreateCountries());
        $this->client->run($this->getCreateStates());
        $this->client->run($this->getCreateWorksInCountry());
        $this->client->run($this->getCreateLivesInCountry());
        $this->client->run($this->getCreateWorksInState());
        $this->client->run($this->getCreateLivesInState());
        $this->client->run($this->getCreateWorksAs());
        $this->client->run($this->getCreateStates());
        $this->client->run($this->getCreateWorksAs());
        $this->client->run($this->getCreateCurrentDiagnosis());
        $this->client->run($this->getCreateSelfDiagnosis());
        $this->client->run($this->getCreateProfessionalDiagnosis());
    }

    /**
     * Cypher query to create a Survey node.
     *
     * @return string CQL
     */
    public function getCreateSurvey()
    {
        return 'MERGE (s:Survey { id: 1 }) SET s.year = 2016';
    }

    /**
     * Cypher query to remove duplicate question nodes.
     *
     * The survey data includes some few duplicated questions due to the way
     * some questions and choices are related (Ex. "If yes, what condition(s)
     * have you been diagnosed with?"). This query consolidates those duplicate
     * nodes and moves all the ANSWER relationships to that single node.
     *
     * @return string CQL
     */
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

    /**
     * Creates list of questions in asked order.
     *
     * @return string CQL
     */
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

    /**
     * Finishes creating linked list by attaching the Question list to the
     * Survey node.
     *
     * @return string CQL
     */
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

    /**
     * Creates Country nodes present in survey responses.
     *
     * @return string CQL
     */
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

    /**
     * Creates State nodes present in survey responses.
     *
     * @return string CQL
     */
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

    /**
     * Create LIVES_IN Country relationships.
     *
     * @return string CQL
     */
    public function getCreateLivesInCountry()
    {
        return <<<CQL
MATCH (q:Question { id: "dropdown_18069141" })-[:HAS_ANSWER]->(a)
WITH DISTINCT a
MATCH (a)<-[:ANSWERED]-(p)
// If answer is United States of America, then answer _must_ include a state,
// so ignore the US answers here.
WHERE NOT a.answer = "United States of America"
WITH a, COLLECT(p) AS residents
MATCH (c:Country { name: a.answer })
WITH c, residents
UNWIND residents AS resident
CREATE UNIQUE (resident)-[:LIVES_IN_COUNTRY]->(c);
CQL;
    }

    /**
     * Create WORKS_IN Country relationships.
     *
     * @return string CQL
     */
    public function getCreateWorksInCountry()
    {
        return <<<CQL
PROFILE
MATCH (q:Question { id: "dropdown_18069205" })-[:HAS_ANSWER]->(a)
WITH DISTINCT a
MATCH (a)<-[:ANSWERED]-(p)
// If answer is United States of America, then answer _must_ include a state,
// so ignore the US answers here.
WHERE NOT a.answer = "United States of America"
WITH a, COLLECT(p) AS residents
MATCH (c:Country { name: a.answer })
WITH c, residents
UNWIND residents AS resident
CREATE UNIQUE (resident)-[:WORKS_IN]->(c);
CQL;
    }

    /**
     * Creates LIVES_IN State relationships.
     *
     * @return string CQL
     */
    public function getCreateLivesInState()
    {
        return <<<CQL
MATCH (q:Question { id: "dropdown_18069210" })-[:HAS_ANSWER]->(a)
WITH a
MATCH (a)<-[:ANSWERED]-(p)
WITH a, COLLECT(p) AS residents
MATCH (s:State { name: a.answer })
MATCH (c:Country { name: 'United States of America' })
WITH c, s, residents
UNWIND residents AS resident
CREATE UNIQUE (resident)-[:LIVES_IN_STATE]->(s)
CREATE UNIQUE (resident)-[:LIVES_IN_COUNTRY]->(c)
CQL;
    }

    /**
     * Creates WORKS_IN State relationships.
     *
     * @return string CQL
     */
    public function getCreateWorksInState()
    {
        return <<<CQL
MATCH (q:Question { id: "dropdown_18069285" })-[:HAS_ANSWER]->(a)
WITH a
MATCH (a)<-[:ANSWERED]-(p)
WITH a, COLLECT(p) AS residents
MATCH (s:State { name: a.answer })
WITH s, residents
UNWIND residents AS resident
CREATE UNIQUE (resident)-[:WORKS_IN]->(s)
CQL;
    }

    /**
     * Creates WORKS_AS relationships.
     *
     * @return string CQL
     */
    public function getCreateWorksAs()
    {
        return <<<CQL
MATCH (q:Question { id: "list_18069282_choice_23064638" })-[:HAS_ANSWER]->(a)<-[:ANSWERED]-(p)
WITH a, COLLECT(p) AS respondents
MERGE (profession:Profession { name: a.answer })
WITH profession, respondents
UNWIND respondents AS respondent
MERGE (respondent)-[:WORKS_AS]->(profession)
CQL;
    }

    /**
     * Creates the CURRENT_DIAGNOSIS relationships.
     *
     * @return string CQL
     */
    public function getCreateCurrentDiagnosis()
    {
        return <<<CQL
MATCH (q:Question { question: "If yes, what condition(s) have you been diagnosed with?" })-[:HAS_ANSWER]->(a)
WITH a
MATCH (a)<-[:ANSWERED]-(p)
WITH a, COLLECT(p) AS respondents
MERGE (d:Disorder { name: a.answer })
WITH d, respondents
UNWIND respondents AS respondent
CREATE UNIQUE (respondent)-[:CURRENT_DIAGNOSIS]->(d);
CQL;
    }

    /**
     * Creates the SELF_DIAGNOSIS relationships.
     *
     * @return string CQL
     */
    public function getCreateSelfDiagnosis()
    {
        return <<<CQL
MATCH (q:Question { question: "If maybe, what condition(s) do you believe you have?" })-[:HAS_ANSWER]->(a)
WITH a
MATCH (a)<-[:ANSWERED]-(p)
WITH a, COLLECT(p) AS respondents
MERGE (d:Disorder { name: a.answer })
WITH d, respondents
UNWIND respondents AS respondent
CREATE UNIQUE (respondent)-[:SELF_DIAGNOSIS]->(d);
CQL;
    }

    /**
     * Creates the PROFESSIONAL_DIAGNOSIS relationships.
     *
     * @return string CQL
     */
    public function getCreateProfessionalDiagnosis()
    {
        return <<<CQL
MATCH (q:Question { question: "If so, what condition(s) were you diagnosed with?" })-[:HAS_ANSWER]->(a)
WITH a
MATCH (a)<-[:ANSWERED]-(p)
WITH a, COLLECT(p) AS respondents
MERGE (d:Disorder { name: a.answer })
WITH d, respondents
UNWIND respondents AS respondent
CREATE UNIQUE (respondent)-[:PROFESSIONAL_DIAGNOSIS]->(d);
CQL;
    }

    /**
     * Updates schema on :Question(field_id).
     *
     * Drops index, adds constraint.
     */
    public function updateQuestionFieldIdSchema()
    {
        try {
            $this->client->run(sprintf('DROP INDEX ON :%s(%s)', 'Question', 'field_id'));
        } catch (Neo4jException $e) {
            if (strpos($e->getMessage(), 'Index belongs to constraint') === false) {
                throw $e;
            }

            // If the property already belongs to a constraint, then the index
            // has been already been dropped and should not be dropped again.
            // As such one may ignore this exception.
        }

        $this->client->run(sprintf(
            'CREATE CONSTRAINT ON (n:%s) ASSERT n.%s IS UNIQUE',
            'Question',
            'field_id'
        ));
    }
}
