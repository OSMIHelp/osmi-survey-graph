<?php

/**
 * OSMI Survey Graph project.
 *
 * @link https://github.com/OSMIHelp/osmi-survey-graph
 */
namespace OSMI\Survey\Graph;

use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serializer;
use OSMI\Survey\Graph\Model\Question;

/**
 * @Serializer\XmlRoot("response")
 *
 * @Hateoas\Relation("self", href = @Hateoas\Route(
 *          "responses_get_one",
 *          parameters = {
 *              "questionId" = "expr(object.getId())"
 *          }
 *      )
 *  )
 *
 * @Hateoas\Relation("question", href = @Hateoas\Route(
 *          "questions_get_one",
 *          parameters = {
 *              "id" = "expr(object.getQuestion().getId())"
 *          }
 *      ),
 *      embedded = "expr(object.getQuestion())",
 *      exclusion = @Hateoas\Exclusion(
 *          excludeIf = "expr(object.getQuestion() === null)"
 *      )
 * )
 */
final class Response
{
    /**
     * @Serializer\XmlAttribute
     */
    private $id;

    /**
     * @var Question
     *
     * @Serializer\Exclude
     */
    private $question;
    private $questionText;
    private $answers = [];
    private $totalAnswers;

    /**
     * Public constructor.
     *
     * @param string $question Question asked
     * @param array  $answers  Answers to $question
     */
    public function __construct(Question $question, array $answers = [])
    {
        $this->id = $question->getId();
        $this->question = $question;
        $this->answers = $answers;
        $this->sortAnswers();
        $this->totalAnswers = $this->sumAnswers();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * Get question.
     *
     * @return string
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * Get answers.
     *
     * @return array
     */
    public function getAnswers()
    {
        return $this->answers;
    }

    /**
     * Gets total number of answers.
     *
     * @return int
     */
    public function getTotalAnswers()
    {
        return $this->totalAnswers;
    }

    /**
     * Gets sum of all answers.
     *
     * Useful for calculating percentage of total answers.
     *
     * @return int
     */
    public function sumAnswers()
    {
        return (int) array_reduce($this->answers, function ($carry, $answer) {
            $carry += $answer['responses'];

            return $carry;
        });
    }

    /**
     * Sorts answers by number of responses.
     */
    private function sortAnswers()
    {
        usort($this->answers, function ($a, $b) {
            if ($a['responses'] === $b['responses']) {
                return 0;
            }

            return ($a['responses'] > $b['responses']) ? -1 : 1;
        });
    }
}
