<?php

namespace OSMI\Survey\Graph\Model;

use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serializer;

/**
 * @Serializer\XmlRoot("answer")
 *
 * @Hateoas\Relation("self", href = @Hateoas\Route(
 *          "disorders_get_one",
 *          parameters = {
 *              "uuid" = "expr(object.getUuid())"
 *          }
 *      )
 *  )
 *
 * @Hateoas\Relation("all_diagnoses", href = @Hateoas\Route(
 *          "disorder_get_respondents",
 *          parameters = {
 *              "uuid" = "expr(object.getUuid())"
 *          }
 *      )
 *  )
 *
 * @Hateoas\Relation("self_diagnoses", href = @Hateoas\Route(
 *          "disorder_get_respondents",
 *          parameters = {
 *              "uuid" = "expr(object.getUuid())",
 *              "type" = "self"
 *          }
 *      )
 *  )
 *
 * @Hateoas\Relation("professional_diagnoses", href = @Hateoas\Route(
 *          "disorder_get_respondents",
 *          parameters = {
 *              "uuid" = "expr(object.getUuid())",
 *              "type" = "professional"
 *          }
 *      )
 *  )
 *
 * @Hateoas\Relation("current_diagnoses", href = @Hateoas\Route(
 *          "disorder_get_respondents",
 *          parameters = {
 *              "uuid" = "expr(object.getUuid())",
 *              "type" = "current"
 *          }
 *      )
 *  )
 */
class Disorder extends AbstractModel
{
    /**
     * @Serializer\XmlAttribute
     */
    protected $uuid;
    protected $name;

    public function getUuid()
    {
        return $this->uuid;
    }

    public function getName()
    {
        return $this->name;
    }
}
