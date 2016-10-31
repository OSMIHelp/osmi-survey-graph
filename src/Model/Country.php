<?php

namespace OSMI\Survey\Graph\Model;

use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serializer;





/**
 * @Serializer\XmlRoot("country")
 *
 * @Hateoas\Relation("self", href = @Hateoas\Route(
 *          "countries_get_one",
 *          parameters = {
 *              "uuid" = "expr(object.getUuid())"
 *          }
 *      )
 *  )
 *
 * @Hateoas\Relation("all_countries", href = @Hateoas\Route(
 *          "countries_get_all",
 *          parameters = {
 *              "uuid" = "expr(object.getUuid())"
 *          }
 *      )
 *  )
 *

 * @Hateoas\Relation("respondents_living_in", href = @Hateoas\Route(
 *          "countries_get_persons_living",
 *          parameters = {
 *              "uuid" = "expr(object.getUuid())"
 *          }
 *      )
 *  )
 *
 * @Hateoas\Relation("respondents_working_in", href = @Hateoas\Route(
 *          "countries_get_persons_working",
 *          parameters = {
 *              "uuid" = "expr(object.getUuid())"
 *          }
 *      )
 *  )
 *
 */
class Country extends AbstractModel
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
