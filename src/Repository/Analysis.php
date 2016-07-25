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
    public function findQuestion($uuid)
    {
        $cql = <<<CQL
MATCH (q:Question { uuid: { uuid }})-[:HAS_ANSWER]->(a)<-[:ANSWERED]-()
WITH q, a, COUNT(*) AS responses
WITH q, COLLECT({ answer: a, responses: responses }) AS answers
RETURN q, answers, REDUCE(totalResponses = 0, n IN answers | totalResponses + n.responses) AS totalResponses
CQL;

        $params = [
            'uuid' => $uuid,
        ];

        $result = $this->client->run($cql, $params);

        return $this->buildSingleQuestion($result);
    }

    /**
     * Find all Questions.
     *
     * @return Question[]
     */
    public function findAllQuestions($skip = 0, $limit = 100)
    {
        $questions = [];

        $cql = <<<CQL
MATCH (q:Question)-[:HAS_ANSWER]->(a)<-[:ANSWERED]-()
WITH q, a, COUNT(*) AS responses
WITH q, COLLECT({ answer: a, responses: responses }) AS answers
RETURN q, REDUCE(totalResponses = 0, n IN answers | totalResponses + n.responses) AS totalResponses, answers
ORDER BY q.order
SKIP { skip }
LIMIT { limit }
CQL;

        $params = [
            'skip' => $skip,
            'limit' => $limit,
        ];

        $result = $this->client->run($cql, $params);

        return $this->buildQuestions($result);
    }

    /**
     * Finds single Answer.
     *
     * @return Answer
     */
    public function findAnswer($uuid)
    {
        $cql = <<<CQL
MATCH (q)-[:HAS_ANSWER]->(a:Answer { uuid: { uuid }})<-[:ANSWERED]-()
RETURN q, a, COUNT(*) AS responses
CQL;

        $params = [
            'uuid' => $uuid,
        ];

        $result = $this->client->run($cql, $params);
        $record = $result->getRecord();
        $data = $record->get('a')->values();
        $data['responses'] = $record->get('responses');
        $question = new Question($record->get('q')->values());

        return new Answer($data, $question);
    }

    /**
     * Find all respondents.
     *
     * @return Person[]
     */
    public function findAllRespondents($skip = 0, $limit = 100)
    {
        $cql = <<<CQL
MATCH (p:Person)
RETURN p
ORDER BY p.token
SKIP { skip }
LIMIT { limit }
CQL;

        $params = [
            'skip' => $skip,
            'limit' => $limit,
        ];

        $result = $this->client->run($cql, $params);
        $resources = [];

        foreach ($result->records() as $record) {
            $data = $record->get('p')->values();
            $resources[] = new Person($data);
        }

        return $resources;
    }

    /**
     * Find all answers belonging to specified question.
     *
     * @param string $uuid Question UUID
     *
     * @return Answer[]
     */
    public function findAllAnswersByQuestion($uuid)
    {
        $cql = <<<CQL
MATCH (q:Question { uuid: { uuid }})-[:HAS_ANSWER]->(a)
RETURN q, a;
CQL;

        $params = [
            'uuid' => $uuid,
        ];

        $result = $this->client->run($cql, $params);
        $resources = [];

        $question = new Question($result->getRecord()->get('q')->values());

        foreach ($result->records() as $record) {
            $data = $record->get('a')->values();
            $resources[] = new Answer($data, $question);
        }

        return $resources;
    }

    /**
     * Find all respondents who responded to a question with the given answer.
     *
     * @return Person[]
     */
    public function findAllRespondentsByAnswer($uuid, $skip = 0, $limit = 100)
    {
        $cql = <<<CQL
MATCH (a:Answer { uuid: { uuid }})<-[:ANSWERED]-(p)
RETURN p
ORDER BY p.token
SKIP { skip }
LIMIT { limit }
CQL;

        $params = [
            'uuid' => $uuid,
            'skip' => $skip,
            'limit' => $limit,
        ];

        $result = $this->client->run($cql, $params);
        $resources = [];

        foreach ($result->records() as $record) {
            $data = $record->get('p')->values();
            $resources[] = new Person($data);
        }

        return $resources;
    }

    /**
     * Find all answers by respondent.
     *
     * @return Answer[]
     */
    public function findAllAnswersByRespondent($uuid, $skip = 0, $limit = 100)
    {
        $cql = <<<CQL
MATCH (p:Person { uuid: { uuid }})-[:ANSWERED]->(a)<-[:HAS_ANSWER]-(q)
RETURN a, q
ORDER BY a.hash
SKIP { skip }
LIMIT { limit }
CQL;

        $params = [
            'uuid' => $uuid,
            'skip' => $skip,
            'limit' => $limit,
        ];

        $result = $this->client->run($cql, $params);
        $resources = [];

        foreach ($result->records() as $record) {
            $data = $record->get('a')->values();
            $resources[] = new Answer(
                $data,
                new Question($record->get('q')->values())
            );
        }

        return $resources;
    }

    /**
     * How many answers did this repondent provide?
     *
     * @param string $uuid Person uuid
     *
     * @return int
     */
    public function countAnswersByRespondent($uuid)
    {
        $cql = <<<CQL
MATCH (p:Person { uuid: { uuid }})-[:ANSWERED]->(a)
RETURN COUNT(a) AS count;
CQL;

        $params = [
            'uuid' => $uuid,
        ];

        $result = $this->client->run($cql, $params);

        return (int) $result->getRecord()->get('count');
    }

    /**
     * How many respondents replied with this answer.
     *
     * @param string $uuid Answer uuid
     *
     * @return int
     */
    public function countRespondentsByAnswer($uuid)
    {
        $cql = <<<CQL
MATCH (a:Answer { uuid: { uuid }})<-[:ANSWERED]-(p)
RETURN COUNT(p) AS respondents;
CQL;

        $params = [
            'uuid' => $uuid,
        ];

        $result = $this->client->run($cql, $params);

        return (int) $result->getRecord()->get('respondents');
    }

    /**
     * Finds single respondent.
     *
     * @return Person
     */
    public function findRespondent($uuid)
    {
        $cql = <<<CQL
MATCH (p:Person { uuid: { uuid }})
OPTIONAL MATCH (p)-[:LIVES_IN_COUNTRY]->(countryResidence)
OPTIONAL MATCH (p)-[:LIVES_IN_STATE]->(stateResidence)
OPTIONAL MATCH (p)-[:WORKS_IN]->(stateWork)
OPTIONAL MATCH (p)-[:WORKS_AS]->(profession)
RETURN p, countryResidence, stateResidence, stateWork, profession
CQL;

        $params = [
            'uuid' => $uuid,
        ];

        $result = $this->client->run($cql, $params);
        $record = $result->getRecord();
        $data = $record->get('p')->values();

        $respondent = new Person($data);

        return $respondent;
    }

    /**
     * How many named resources exist.
     *
     * @return string $name Resource (node) name
     */
    public function countResources($name)
    {
        $cql = sprintf(
            'MATCH (n:%s) RETURN COUNT(n) AS count;',
            ucfirst(strtolower($name))
        );
        $result = $this->client->run($cql);

        return (int) $result->getRecord()->get('count');
    }

    private function buildSingleQuestion(Result $result)
    {
        $resources = $this->buildQuestions($result);

        if (empty($resources)) {
            return;
        }

        return $resources[0];
    }

    private function buildQuestions(Result $result)
    {
        $resources = [];

        foreach ($result->getRecords() as $record) {
            $data = $record->get('q')->values();
            $data['totalResponses'] = $record->get('totalResponses');
            $question = new Question($data);

            foreach ($record->get('answers') as $collection) {
                $answerData = $collection['answer']->values();
                $answerData['responses'] = $collection['responses'];
                $question->addAnswer(new Answer($answerData));
            }

            $resources[] = $question;
        }

        return $resources;
    }
}
