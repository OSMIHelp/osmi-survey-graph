<?php

/**
 * OSMI Survey Graph project.
 *
 * @link https://github.com/OSMIHelp/osmi-survey-graph
 */
namespace OSMI\Survey\Graph\Repository;

use GraphAware\Neo4j\Client\Formatter\Result;
use OSMI\Survey\Graph\Response;

/**
 * Performs OSMI survey data analysis.
 */
class Analysis extends Neo4j
{
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
RETURN q.question AS question, COLLECT({ answer: a.answer , responses: responses }) AS answers;
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
                $record->get('question'),
                $record->get('answers')
            );
        }

        return $responses;
    }
}
