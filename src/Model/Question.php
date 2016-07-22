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
 */
class Question extends AbstractModel
{
    /**
     * @Serializer\XmlAttribute
     */
    protected $id;
    protected $fieldId;
    protected $question;
    protected $answers;
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

    public function getOrder()
    {
        return $this->order;
    }
}
