<?php

namespace OSMI\Survey\Graph\Model;

use JMS\Serializer\Annotation as Serializer;

/**
 * @Serializer\XmlRoot("answer")
 */
class Answer extends AbstractModel
{
    /**
     * @Serializer\XmlAttribute
     */
    protected $hash;
    protected $answer;

    public function getHash()
    {
        return $this->hash;
    }

    public function getAnswer()
    {
        return $this->answer;
    }
}
