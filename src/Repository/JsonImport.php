<?php

/**
 * OSMI Survey Graph project.
 *
 * @link https://github.com/OSMIHelp/osmi-survey-graph
 */
namespace OSMI\Survey\Graph\Repository;

/**
 * Responsible for importing JSON-sourced (API result format) data into Neo4j.
 */
class JsonImport extends Neo4j
{
    private $stack;

    public function import(array $data)
    {
        $this->stack = $this->client->stack();
        $this->processQuestions($data['questions']);
        $this->processResponses($data['responses']);
        $this->client->runStack($this->stack);
        $this->stack = null;
    }

    private function processQuestions(array $questions)
    {
        $order = 0;

        $groupParams = [];
        $questionParams = [];

        foreach ($questions as $question) {
            // Skip statements
            if (strpos($question['id'], 'statement') !== false) {
                continue;
            }

            $question['question'] = strip_tags($question['question']);

            // Groups are their own nodes
            if (strpos($question['id'], 'group') !== false) {
                $groupParams[] = $question;
                continue;
            }

            if (!isset($question['group'])) {
                $question['group'] = null;
            }

            $question['order'] = $order;
            $questionParams[] = $question;
            $order++;
        }

        $this->stack->push($this->getCreateGroup(), ['params' => $groupParams]);
        $this->stack->push($this->getCreateQuestion(), ['params' => $questionParams]);
    }

    private function processResponses(array $responses)
    {
        $answerParams = [];
        $personParams = [];

        foreach ($responses as $response) {
            $metadata = $this->processMetadata($response['metadata']);
            $params = ['token' => $response['token'], 'props' => $metadata];
            $personParams[] = $params;

            foreach ($response['answers'] as $questionId => $answer) {
                if (strlen(trim($answer)) === 0) {
                    continue;
                }

                // This hash allows for a unique ID for Answer nodes
                $hash = md5($questionId . $answer);
                $params = [
                    'hash' => $hash,
                    'questionId' => $questionId,
                    'answer' => $answer,
                    'token' => $response['token'],
                ];

                $answerParams[] = $params;
            }
        }

        $this->stack->push($this->getCreatePerson(), ['params' => $personParams]);
        $this->stack->push($this->getCreateAnswer(), ['params' => $answerParams]);
    }

    private function processMetadata(array $metadata)
    {
        $metadata = array_map(function ($value) {
            if (strlen(trim($value)) === 0) {
                return;
            }

            return $value;
        }, $metadata);

        if (isset($metadata['date_land']) && $metadata['date_land'] !== '0000-00-00 00:00:00') {
            // Convert to Java timestamp
            $metadata['date_land'] = (int) strtotime($metadata['date_land']) * 1000;
        }

        if (isset($metadata['date_submit']) && $metadata['date_submit'] !== '0000-00-00 00:00:00') {
            // Convert to Java timestamp
            $metadata['date_submit'] = (int) strtotime($metadata['date_submit']) * 1000;
        }

        return $metadata;
    }

    public function getCreateGroup()
    {
        return <<<CQL
WITH { params } AS params
UNWIND params AS group
MERGE (g:Group { id: group.id })
ON CREATE SET
    g.description = group.question,
    g.field_id = group.field_id
CQL;
    }

    public function getCreateQuestion()
    {
        return <<<CQL
WITH { params } AS params
UNWIND params AS question
MERGE (q:Question { id: question.id })
ON CREATE SET
    q.question = question.question,
    q.field_id = question.field_id,
    q.group = question.group,
    q.order = question.order
WITH q
MATCH (g:Group { id: q.group })
MERGE (g)-[:CONTAINS]->(q);
CQL;
    }

    public function getCreateAnswer()
    {
        return <<<CQL
WITH { params } AS params
UNWIND params AS answer
MERGE (a:Answer { hash: answer.hash })
ON CREATE SET a.answer = answer.answer
WITH DISTINCT answer, a
MATCH (q:Question { id: answer.questionId })
MATCH (p:Person { token: answer.token })
MERGE (q)-[:HAS_ANSWER]->(a)
MERGE (p)-[:ANSWERED]->(a)
CQL;
    }

    public function getCreatePerson()
    {
        return <<<CQL
WITH { params } AS params
UNWIND params AS person
MERGE (p:Person { token: person.token })
ON CREATE SET p += person.props
CQL;
    }
}
