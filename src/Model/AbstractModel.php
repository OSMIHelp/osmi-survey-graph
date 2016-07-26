<?php

namespace OSMI\Survey\Graph\Model;

abstract class AbstractModel
{
    public function __construct(array $data)
    {
        foreach ($data as $property => $value) {
            // snake to camel case
            $property = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $property))));

            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }
}
