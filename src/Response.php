<?php

/**
 * OSMI Survey Graph project.
 *
 * @link https://github.com/OSMIHelp/osmi-survey-graph
 */
namespace OSMI\Survey\Graph;

/**
 * ValueObject containing a single Survey response.
 */
final class Response
{
    private $question;
    private $answers = [];

    /**
     * Public constructor.
     *
     * @param string $question Question asked
     * @param array  $answers  Answers to $question
     */
    public function __construct($question, array $answers = [])
    {
        $this->question = $question;
        $this->answers = $answers;
        $this->sortAnswers();
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
     * Get total of all answers.
     *
     * Useful for calculating percentage of total answers.
     *
     * @return int
     */
    public function totalAnswers()
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
