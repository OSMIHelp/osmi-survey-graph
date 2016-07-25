<?php

namespace OSMI\Survey\Graph\Model;

use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serializer;

/**
 * @Serializer\XmlRoot("person")
 *
 * @Hateoas\Relation("self", href = @Hateoas\Route(
 *          "respondents_get_one",
 *          parameters = {
 *              "uuid" = "expr(object.getUuid())"
 *          }
 *      )
 *  )
 *
 * @Hateoas\Relation("answers", href = @Hateoas\Route(
 *          "respondent_answers",
 *          parameters = {
 *              "uuid" = "expr(object.getUuid())"
 *          }
 *      )
 *  )
 */
class Person extends AbstractModel
{
    /**
     * @Serializer\XmlAttribute
     */
    protected $uuid;
    protected $token;
    /**
     * @Serializer\Exclude
     */
    protected $networkId;
    protected $dateSubmit;
    protected $browser;
    protected $dateLand;
    protected $userAgent;
    protected $platform;
    
    public function getUuid()
    {
        return $this->uuid;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getNetworkId()
    {
        return $this->networkId;
    }

    public function getDateSubmit()
    {
        return $this->dateSubmit;
    }

    public function getBrowser()
    {
        return $this->browser;
    }

    public function getDateLand()
    {
        return $this->dateLand;
    }

    public function getUserAgent()
    {
        return $this->userAgent;
    }

    public function getPlatform()
    {
        return $this->platform;
    }
}
