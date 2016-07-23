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
    public function findQuestion($id)
    {
        $cql = <<<CQL
MATCH (q:Question { id: { id }})-[:HAS_ANSWER]->(a)<-[:ANSWERED]-()
WITH q, a, COUNT(*) AS responses
WITH q, COLLECT({ answer: a, responses: responses }) AS answers
RETURN q, answers, REDUCE(totalResponses = 0, n IN answers | totalResponses + n.responses) AS totalResponses
CQL;

        $params = [
            'id' => $id,
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
    public function findAnswer($hash)
    {
        $cql = <<<CQL
MATCH (q)-[:HAS_ANSWER]->(a:Answer { hash: { hash }})<-[:ANSWERED]-()
RETURN q, a, COUNT(*) AS responses
CQL;

        $params = [
            'hash' => $hash,
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
     * Find all respondents who responded to a question with the given answer.
     *
     * @return Person[]
     */
    public function findAllRespondentsByAnswer($hash, $skip = 0, $limit = 100)
    {
        $cql = <<<CQL
MATCH (a:Answer { hash: { hash }})<-[:ANSWERED]-(p)
RETURN p
ORDER BY p.token
SKIP { skip }
LIMIT { limit }
CQL;

        $params = [
            'hash' => $hash,
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
    public function findAllAnswersByRespondent($token, $skip = 0, $limit = 100)
    {
        $cql = <<<CQL
MATCH (p:Person { token: { token }})-[:ANSWERED]->(a)<-[:HAS_ANSWER]-(q)
RETURN a, q
ORDER BY a.hash
SKIP { skip }
LIMIT { limit }
CQL;

        $params = [
            'token' => $token,
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
     * @param string $token Person token
     *
     * @return int
     */
    public function countAnswersByRespondent($token)
    {
        $cql = <<<CQL
MATCH (p:Person { token: { token }})-[:ANSWERED]->(a)
RETURN COUNT(a) AS count;
CQL;

        $params = [
            'token' => $token,
        ];

        $result = $this->client->run($cql, $params);

        return (int) $result->getRecord()->get('count');
    }

    /**
     * How many respondents replied with this answer.
     *
     * @param string $hash Answer hash
     *
     * @return int
     */
    public function countRespondentsByAnswer($hash)
    {
        $cql = <<<CQL
MATCH (a:Answer { hash: { hash }})<-[:ANSWERED]-(p)
RETURN COUNT(p) AS respondents;
CQL;

        $params = [
            'hash' => $hash,
        ];

        $result = $this->client->run($cql, $params);

        return (int) $result->getRecord()->get('respondents');
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
     * How many named resources exist.
     *
     * @return string $name Resource name
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
