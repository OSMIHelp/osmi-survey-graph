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
 *              "id" = "expr(object.getId())"
 *          }
 *      )
 *  )
 *
 * @Hateoas\Relation(
 *      name = "answers",
 *      embedded = @Hateoas\Embedded(
 *          "expr(object.getAnswers())",
 *          exclusion = @Hateoas\Exclusion(
 *              excludeIf = "expr(false !== object.hasAnswers())"
 *          )
 *      )
 * )
 */
class Question extends AbstractModel
{
    /**
     * @Serializer\XmlAttribute
     */
    protected $id;
    protected $fieldId;
    protected $question;

    /**
     * @Serializer\Exclude
     */
    protected $answers = [];
    protected $totalResponses = 0;
    protected $order;

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

    public function getTotalResponses()
    {
        return $this->totalResponses;
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
        return empty($this->answers) !== false;
    }
}
