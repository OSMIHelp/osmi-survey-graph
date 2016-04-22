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
final class Response implements \JsonSerializable
{
    private $question;
    private $answers = [];
    private $totalAnswers;

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
        $this->totalAnswers = $this->sumAnswers();
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

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return [
            'question' => $this->getQuestion(),
            'answers' => $this->getAnswers(),
            'totalAnswers' => $this->getTotalAnswers(),
        ];
    }
}
