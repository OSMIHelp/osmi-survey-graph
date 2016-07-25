<?php

namespace OSMI\Survey\Graph\Model;

use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serializer;

/**
 * @Serializer\XmlRoot("answer")
 *
 * @Hateoas\Relation("self", href = @Hateoas\Route(
 *          "answers_get_one",
 *          parameters = {
 *              "uuid" = "expr(object.getUuid())"
 *          }
 *      )
 *  )
 *
 * @Hateoas\Relation("respondents", href = @Hateoas\Route(
 *          "answer_respondents",
 *          parameters = {
 *              "uuid" = "expr(object.getUuid())"
 *          }
 *      )
 *  )
 *
 * @Hateoas\Relation("question", href = @Hateoas\Route(
 *          "questions_get_one",
 *          parameters = {
 *              "uuid" = "expr(object.getQuestion().getUuid())"
 *          }
 *      ),
 *      embedded = "expr(object.getQuestion())",
 *      exclusion = @Hateoas\Exclusion(
 *          excludeIf = "expr(object.getQuestion() === null)"
 *      )
 * )
 */
class Answer extends AbstractModel
{
    /**
     * @Serializer\XmlAttribute
     */
    protected $uuid;
    protected $hash;
    protected $answer;
    protected $responses = 0;

    /**
     * @Serializer\Exclude
     */
    protected $question;

    public function getUuid()
    {
        return $this->uuid;
    }

    public function __construct(array $data, Question $question = null)
    {
        parent::__construct($data);
        $this->question = $question;
    }

    public function getHash()
    {
        return $this->hash;
    }

    public function getAnswer()
    {
        return $this->answer;
    }

    public function getResponses()
    {
        return $this->responses;
    }

    public function getQuestion()
    {
        return $this->question;
    }
}
