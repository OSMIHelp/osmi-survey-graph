<?php

namespace OSMI\Survey\Graph\Model;

use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serializer;

/**
 * @Serializer\XmlRoot("question")
 *
 * @Hateoas\Relation("self", href = @Hateoas\Route(
 *          "questions_get_one",
 *          parameters = {
 *              "uuid" = "expr(object.getUuid())"
 *          }
 *      )
 *  )
 *
 * @Hateoas\Relation(
 *      name = "answers",
 *      embedded = @Hateoas\Embedded(
 *          "expr(object.getAnswers())",
 *          exclusion = @Hateoas\Exclusion(
 *              excludeIf = "expr(false === object.hasAnswers())"
 *          )
 *      ),
 *      href = @Hateoas\Route(
 *          "question_answers",
 *          parameters = {
 *              "uuid" = "expr(object.getUuid())"
 *          }
 *      )
 * )
 */
class Question extends AbstractModel
{
    /**
     * @Serializer\XmlAttribute
     */
    protected $uuid;
    protected $id;
    protected $fieldId;
    protected $question;

    /**
     * @Serializer\Exclude
     */
    protected $answers = [];
    protected $responses = 0;
    protected $order;

    public function getUuid()
    {
        return $this->uuid;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getFieldId()
    {
        return $this->fieldId;
    }

    public function getQuestion()
    {
        return $this->question;
    }

    public function getAnswers()
    {
        return $this->answers;
    }

    public function getResponses()
    {
        return $this->responses;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function addAnswer(Answer $answer)
    {
        $this->answers[] = $answer;
    }

    public function hasAnswers()
    {
        if (empty($this->answers)) {
            return false;
        }

        return true;
    }
}
