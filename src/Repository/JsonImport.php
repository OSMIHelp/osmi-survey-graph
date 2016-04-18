<?php

namespace OSMI\Survey\Graph\Repository;

class JsonImport extends Neo4j
{
    public function import(array $data)
    {
        $this->processQuestions($data['questions']);
        $this->processResponses($data['responses']);
    }

    private function processQuestions(array $questions)
    {
        $stack = $this->client->stack();
        $order = 0;

        foreach ($questions as $question) {
            // Skip statements
            if (strpos($question['id'], 'statement') !== false) {
                continue;
            }

            $question['question'] = strip_tags($question['question']);

            // Groups are their own nodes
            if (strpos($question['id'], 'group') !== false) {
                $stack->push($this->getCreateGroup(), $question);
                continue;
            }

            if (!isset($question['group'])) {
                $question['group'] = null;
            }

            $question['order'] = $order;
            $stack->push($this->getCreateQuestion(), $question);
            $order++;
        }

        $this->client->runStack($stack);
    }

    private function processResponses(array $responses)
    {
        foreach ($responses as $response) {
            $stack = $this->client->stack();

            $metadata = $this->processMetadata($response['metadata']);
            $params = ['token' => $response['token'], 'props' => $metadata];
            $stack->push($this->getCreatePerson(), $params);

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

                $stack->push($this->getCreateAnswer(), $params);
            }

            $this->client->runStack($stack);
            $stack = null;
        }
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
MERGE (g:Group { id: { id }})
ON CREATE SET
    g.description = { question },
    g.field_id = { field_id }
CQL;
    }

    public function getCreateQuestion()
    {
        return <<<CQL
MERGE (q:Question { id: { id }})
ON CREATE SET
    q.question = { question },
    q.field_id = { field_id },
    q.group = { group },
    q.order = { order }
WITH q
MATCH (g:Group { id: { group }})
MERGE (g)-[:CONTAINS]->(q);
CQL;
    }

    public function getCreateAnswer()
    {
        return <<<CQL
MERGE (a:Answer { hash: { hash }})
ON CREATE SET a.answer = { answer }
WITH a
MATCH (q:Question { id: { questionId }})
MATCH (p:Person { token: { token }})
MERGE (q)-[:HAS_ANSWER]->(a)
MERGE (p)-[:ANSWERED]->(a)
CQL;
    }

    public function getCreatePerson()
    {
        return <<<CQL
MERGE (p:Person { token: { token }})
ON CREATE SET p += { props }
CQL;
    }
}
