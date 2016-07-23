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
 *              "token" = "expr(object.getToken())"
 *          }
 *      )
 *  )
 *
 * @Hateoas\Relation("answers", href = @Hateoas\Route(
 *          "respondent_answers",
 *          parameters = {
 *              "token" = "expr(object.getToken())"
 *          }
 *      )
 *  )
 */
class Person extends AbstractModel
{
    /**
     * @Serializer\XmlAttribute
     */
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
