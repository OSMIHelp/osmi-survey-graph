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
    public function import(array $data)
    {
        $this->importQuestions($data['questions']);
        $this->importResponses($data['responses']);
    }

    private function importQuestions(array $data)
    {
        $groups = [];
        $questions = [];
        $order = 0;

        foreach ($data as $question) {
            // Skip statements
            if (strpos($question['id'], 'statement') !== false) {
                continue;
            }

            $question['question'] = strip_tags($question['question']);

            // Groups are their own nodes
            if (strpos($question['id'], 'group') !== false) {
                $groups[] = $question;
                continue;
            }

            if (!isset($question['group'])) {
                $question['group'] = null;
            }

            $question['order'] = $order;
            $questions[] = $question;
            $order++;
        }

        $stack = $this->client->stack();
        $stack->push($this->getCreateGroups(), ['groups' => $groups]);
        $stack->push($this->getCreateQuestions(), ['questions' => $questions]);

        $this->client->runStack($stack);
    }

    private function importResponses(array $responses)
    {
        $people = [];
        $answers = [];

        foreach ($responses as $response) {
            $metadata = $this->processMetadata($response['metadata']);
            $params = ['token' => $response['token'], 'props' => $metadata];
            $people[] = $params;

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

                $answers[] = $params;
            }
        }

        $stack = $this->client->stack();
        $stack->push($this->getCreateAnswers(), ['answers' => $answers]);
        $stack->push($this->getCreatePeople(), ['people' => $people]);
        $this->client->runStack($stack);
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

    public function getCreateGroups()
    {
        return <<<CQL
UNWIND { groups } AS g
WITH g
MERGE (group:Group { id: g.id })
ON CREATE SET
    group.description = g.question,
    group.field_id = g.field_id
CQL;
    }

    public function getCreateQuestions()
    {
        return <<<CQL
UNWIND { questions } AS q
WITH q
MERGE (question:Question { id: q.id })
ON CREATE SET
    question.question = q.question,
    question.field_id = q.field_id,
    question.group = q.group,
    question.order = q.order
WITH question, q
MATCH (group:Group { id: q.group })
MERGE (group)-[:CONTAINS]->(question);
CQL;
    }

    public function getCreateAnswers()
    {
        return <<<CQL
UNWIND { answers } AS a
WITH a
MERGE (answer:Answer { hash: a.hash })
ON CREATE SET answer.answer = a.answer
WITH answer, a
MATCH (q:Question { id: a.questionId })
MATCH (p:Person { token: a.token })
MERGE (q)-[:HAS_ANSWER]->(answer)
MERGE (p)-[:ANSWERED]->(answer)
CQL;
    }

    public function getCreatePeople()
    {
        return <<<CQL
UNWIND { people } AS p
WITH p
MERGE (person:Person { token: p.token })
ON CREATE SET person += p.props
CQL;
    }
}
