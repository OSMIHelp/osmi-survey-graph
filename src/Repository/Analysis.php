<?php

/**
 * OSMI Survey Graph project.
 *
 * @link https://github.com/OSMIHelp/osmi-survey-graph
 */
namespace OSMI\Survey\Graph\Repository;

use GraphAware\Bolt\Result\Result;
use OSMI\Survey\Graph\Enum\Diagnosis;
use OSMI\Survey\Graph\Model\Answer;
use OSMI\Survey\Graph\Model\Country;
use OSMI\Survey\Graph\Model\Disorder;
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
MATCH (q:Question { uuid: { uuid }})-[:HAS_ANSWER]->(a)
RETURN q, COLLECT(a) AS answers
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
MATCH (q:Question)-[:HAS_ANSWER]->(a)
WITH q, COLLECT(a) AS answers
RETURN q, answers
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
MATCH (q)-[:HAS_ANSWER]->(a:Answer { uuid: { uuid }})
RETURN q, a
CQL;

        $params = [
            'uuid' => $uuid,
        ];

        $result = $this->client->run($cql, $params);
        $record = $result->getRecord();
        $data = $record->get('a')->values();
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
     * Find all disorders.
     *
     * @return Disorder[]
     */
    public function findAllDisorders($skip = 0, $limit = 100)
    {
        $cql = <<<CQL
MATCH (d:Disorder)
RETURN d
ORDER BY d.name
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
            $data = $record->get('d')->values();
            $resources[] = new Disorder($data);
        }

        return $resources;
    }

    /**
     * Finds single Disorder.
     *
     * @return Disorder
     */
    public function findDisorder($uuid)
    {
        $cql = <<<CQL
MATCH (d:Disorder { uuid: { uuid }})
RETURN d
CQL;

        $params = [
            'uuid' => $uuid,
        ];

        $result = $this->client->run($cql, $params);

        return new Disorder($result->getRecord()->get('d')->values());
    }

    /**
     * Finds respondents diagnosed with specified disorder and type of diagnosis.
     *
     * @param string    $uuid  Disorder UUID
     * @param Diagnosis $type  Diagnosis type
     * @param int       $skip
     * @param int       $limit
     *
     * @return Person[]
     */
    public function findRespondentsByDisorder($uuid, Diagnosis $type = null, $skip = 0, $limit = 100)
    {
        $format = <<<FORMAT
MATCH (d:Disorder { uuid: { uuid }})<-[:%s]-(p)
RETURN DISTINCT p, d
ORDER BY p.token
SKIP { skip }
LIMIT { limit }
FORMAT;

        $params = [
            'uuid' => $uuid,
            'skip' => $skip,
            'limit' => $limit,
        ];

        $rel = ($type === null) ? implode('|', Diagnosis::keys()) : $type->getKey();
        $cql = sprintf($format, $rel);
        $result = $this->client->run($cql, $params);
        $resources = [];

        foreach ($result->records() as $record) {
            $data = $record->get('p')->values();
            $resources[] = new Person($data);
        }

        return $resources;
    }

    /**
     * Finds respondent diagnoses
     *
     * @param string    $uuid  Person UUID
     * @param Diagnosis $type  Diagnosis type
     * @param int       $skip
     * @param int       $limit
     *
     * @return Disorder[]
     */
    public function findDisordersByRespondent($uuid, Diagnosis $type = null, $skip = 0, $limit = 100)
    {
        $format = <<<FORMAT
MATCH (p:Person { uuid: { uuid }})-[:%s]->(d)
RETURN DISTINCT d, p
ORDER BY d.name
SKIP { skip }
LIMIT { limit }
FORMAT;

        $params = [
            'uuid' => $uuid,
            'skip' => $skip,
            'limit' => $limit,
        ];

        $rel = ($type === null) ? implode('|', Diagnosis::keys()) : $type->getKey();
        $cql = sprintf($format, $rel);
        $result = $this->client->run($cql, $params);
        $resources = [];

        foreach ($result->records() as $record) {
            $data = $record->get('d')->values();
            $resources[] = new Disorder($data);
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
        $question = null;

        foreach ($result->records() as $record) {
            if ($question === null) {
                $question = new Question($record->get('q')->values());
            }

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
     * How many respondents are diagnosed with the specified disorder?
     *
     * @param string    $uuid Disorder UUID
     * @param Diagnosis $type Diagnosis type
     *
     * @return int
     */
    public function countRespondentsByDisorder($uuid, Diagnosis $type = null)
    {
        $format = <<<FORMAT
MATCH (d:Disorder { uuid: { uuid }})<-[:%s]-(p)
RETURN COUNT(DISTINCT p) AS count;
FORMAT;

        $params = [
            'uuid' => $uuid,
        ];

        $rel = ($type === null) ? implode('|', Diagnosis::keys()) : $type->getKey();
        $cql = sprintf($format, $rel);
        $result = $this->client->run($cql, $params);

        return (int) $result->getRecord()->get('count');
    }

    /**
     * How many total disorders has the respondent been diagnosed with?
     *
     * @param string    $uuid Person UUID
     * @param Diagnosis $type Diagnosis type
     *
     * @return int
     */
    public function countDisordersByRespondent($uuid, Diagnosis $type = null)
    {
        $format = <<<FORMAT
MATCH (p:Person { uuid: { uuid }})-[:%s]->(d)
RETURN COUNT(DISTINCT d) AS count;
FORMAT;

        $params = [
            'uuid' => $uuid,
        ];

        $rel = ($type === null) ? implode('|', Diagnosis::keys()) : $type->getKey();
        $cql = sprintf($format, $rel);
        $result = $this->client->run($cql, $params);

        return (int) $result->getRecord()->get('count');
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
            $question = new Question($data);

            foreach ($record->get('answers') as $answer) {
                $answerData = $answer->values();
                $question->addAnswer(new Answer($answerData));
            }

            $resources[] = $question;
        }

        return $resources;
    }


    private function buildCountries(Result $result)
    {
        $resources = [];

        foreach ($result->getRecords() as $record) {
            $data = $record->get('c')->values();
            $question = new Country($data);

            $resources[] = $question;
        }

        return $resources;
    }

    private function buildSingleCountry($result) {
        $resources = $this->buildCountries($result);

        if (empty($resources)) {
            return;
        }

        return $resources[0];
    }


    /**
     * Find all Countries.
     *
     * @return Country[]
     */
    public function findAllCountries($skip = 0, $limit = 100)
    {
        $cql = <<<CQL
MATCH (c:Country)<-[]-(p:Person)
RETURN DISTINCT c
ORDER BY c.name
SKIP { skip }
LIMIT { limit }
CQL;

        $params = [
            'skip' => $skip,
            'limit' => $limit,
        ];

        $result = $this->client->run($cql, $params);

        return $this->buildCountries($result);
    }

    /**
     * Find a single country by UUID
     *
     * @param string $uuid
     * @return mixed|void
     */
    public function findCountry($uuid) {
        $cql = <<<CQL
MATCH (c:Country { uuid: { uuid }})<-[]-(p:Person)
RETURN c, COLLECT(p) AS persons
CQL;

        $params = [
            'uuid' => $uuid,
        ];

        $result = $this->client->run($cql, $params);

        return $this->buildSingleCountry($result);
    }

    /**
     * @param string $uuid
     * @param int $skip
     * @param int $limit
     * @return Person[]
     */
    public function findRespondentsLivingInCountry($uuid, $skip = 0, $limit = 100) {
        $cql = <<<CQL
MATCH (c:Country { uuid: { uuid }})<-[:LIVES_IN_COUNTRY]-(p)
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
     * @param string $uuid
     * @param int $skip
     * @param int $limit
     * @return Person[]
     */
    public function findRespondentsWorkingInCountry($uuid, $skip = 0, $limit = 100) {
        $cql = <<<CQL
MATCH (c:Country { uuid: { uuid }})<-[:WORKS_IN]-(p)
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

    public function findWorkingCountryByRespondent($uuid) {
        $cql = <<<CQL
MATCH (p:Person { uuid: { uuid }})-[:WORKS_IN]->(c:Country)
RETURN c
CQL;
        $params = [
            'uuid' => $uuid,
        ];

        $result = $this->client->run($cql, $params);

        return $this->buildSingleCountry($result);
    }

    public function findLivingCountryByRespondent($uuid) {
        $cql = <<<CQL
MATCH (p:Person { uuid: { uuid }})-[:LIVES_IN_COUNTRY]->(c:Country)
RETURN c
CQL;
        $params = [
            'uuid' => $uuid,
        ];

        $result = $this->client->run($cql, $params);

        return $this->buildSingleCountry($result);
    }
}
