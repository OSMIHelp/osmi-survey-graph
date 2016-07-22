<?php

/**
 * OSMI Survey Graph project.
 *
 * @link https://github.com/OSMIHelp/osmi-survey-graph
 */
namespace OSMI\Survey\Graph\Repository;

use GraphAware\Bolt\Result\Result;
use OSMI\Survey\Graph\Model\Answer;
use OSMI\Survey\Graph\Model\Person;
use OSMI\Survey\Graph\Model\Question;
use OSMI\Survey\Graph\Response;

/**
 * Performs OSMI survey data analysis.
 */
class Analysis extends Neo4j
{
    /**
     * Finds single Question.
     *
     * @return Question
     */
    public function findQuestion($id)
    {
        $cql = <<<CQL
MATCH (q:Question { id: { id }})-[:HAS_ANSWER]->(a)
RETURN q, COLLECT(a) AS answers
CQL;

        $params = [
            'id' => $id,
        ];

        $result = $this->client->run($cql, $params);
        $record = $result->getRecord();
        $data = $record->get('q')->values();

        foreach ($record->get('answers') as $answer) {
            $data['answers'][] = new Answer($answer->values());
        }

        $question = new Question($data);

        return $question;
    }

    /**
     * Find all Questions.
     *
     * @return Question[]
     */
    public function findAllQuestions($skip = 0, $limit = 0)
    {
        $questions = [];

        $cql = <<<CQL
MATCH (q:Question)-[:HAS_ANSWER]->(a)
RETURN q, COLLECT(a) AS answers
ORDER BY q.order
CQL;

        $params = [];

        if ($skip > 0) {
            $cql .= ' SKIP { skip }';
            $params['skip'] = $skip;
        }

        if ($limit > 0) {
            $cql .= ' LIMIT { limit }';
            $params['limit'] = $limit;
        }

        $result = $this->client->run($cql, $params);

        foreach ($result->records() as $record) {
            $data = $record->get('q')->values();

            foreach ($record->get('answers') as $answer) {
                $data['answers'][] = new Answer($answer->values());
            }

            $questions[] = new Question($data);
        }

        return $questions;
    }

    /**
     * Find all respondents.
     *
     * @return Person[]
     */
    public function findAllRespondents($skip = 0, $limit = 0)
    {
        $cql = <<<CQL
MATCH (p:Person)
OPTIONAL MATCH (p)-[:LIVES_IN_COUNTRY]->(countryResidence)
OPTIONAL MATCH (p)-[:LIVES_IN_STATE]->(stateResidence)
OPTIONAL MATCH (p)-[:WORKS_IN]->(stateWork)
OPTIONAL MATCH (p)-[:WORKS_AS]->(profession)
RETURN p, countryResidence, stateResidence, stateWork, profession
ORDER BY p.token
CQL;

        $params = [];

        if ($skip > 0) {
            $cql .= ' SKIP { skip }';
            $params['skip'] = $skip;
        }

        if ($limit > 0) {
            $cql .= ' LIMIT { limit }';
            $params['limit'] = $limit;
        }

        $result = $this->client->run($cql, $params);
        $entities = [];

        foreach ($result->records() as $record) {
            $data = $record->get('p')->values();
            $entities[] = new Person($data);
        }

        return $entities;
    }

    /**
     * Finds single respondent.
     *
     * @return Person
     */
    public function findRespondent($token)
    {
        $cql = <<<CQL
MATCH (p:Person { token: { token }})
OPTIONAL MATCH (p)-[:LIVES_IN_COUNTRY]->(countryResidence)
OPTIONAL MATCH (p)-[:LIVES_IN_STATE]->(stateResidence)
OPTIONAL MATCH (p)-[:WORKS_IN]->(stateWork)
OPTIONAL MATCH (p)-[:WORKS_AS]->(profession)
RETURN p, countryResidence, stateResidence, stateWork, profession
CQL;

        $params = [
            'token' => $token,
        ];

        $result = $this->client->run($cql, $params);
        $record = $result->getRecord();
        $data = $record->get('p')->values();

        $respondent = new Person($data);

        return $respondent;
    }

    /**
     * How many questions are in the survey.
     *
     * @return int
     */
    public function countQuestions()
    {
        $result = $this->client->run('MATCH (n:Question) RETURN COUNT(n) AS questions;');

        return (int) $result->getRecord()->get('questions');
    }

    /**
     * How many people responded to the survey?
     *
     * @return int
     */
    public function countRespondents()
    {
        $result = $this->client->run('MATCH (n:Person) RETURN COUNT(n) AS respondents;');

        return (int) $result->getRecord()->get('respondents');
    }

    /**
     * Gets a the response to a single question.
     *
     * @param string $questionId
     *
     * @return array
     */
    public function getSingleReponse($questionId)
    {
        $cql = <<<CQL
MATCH (q:Question { id: { questionId }})-[:HAS_ANSWER]->(a)<-[:ANSWERED]-()
WITH q, a, COUNT(*) AS responses
RETURN q AS question, COLLECT({ answer: a.answer , responses: responses }) AS answers;
CQL;

        $params = [
            'questionId' => $questionId,
        ];

        $result = $this->client->run($cql, $params);

        return $this->buildSingleResponse($result);
    }

    public function getResponses($skip = 0, $limit = 200)
    {
        $cql = <<<CQL
MATCH (q:Question)
WITH q
ORDER BY q.order
SKIP { skip }
LIMIT { limit }
MATCH (q)-[:HAS_ANSWER]->(a)<-[:ANSWERED]-()
WITH q, a, COUNT(*) AS responses
RETURN q.order AS order, q.question AS question, COLLECT({ answer: a.answer , responses: responses }) AS answers
ORDER BY q.order;
CQL;

        $params = [
            'skip' => $skip,
            'limit' => $limit,
        ];

        $result = $this->client->run($cql, $params);

        return $this->buildResponses($result);
    }

    private function buildSingleResponse(Result $result)
    {
        $responses = $this->buildResponses($result);

        if (empty($responses)) {
            return;
        }

        return $responses[0];
    }

    private function buildResponses(Result $result)
    {
        $responses = [];

        foreach ($result->getRecords() as $record) {
            
            $responses[] = new Response(
                new Question($record->get('question')->values()),
                $record->get('answers')
            );
        }

        return $responses;
    }
}
