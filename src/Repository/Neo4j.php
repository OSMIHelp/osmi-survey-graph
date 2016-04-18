<?php

namespace OSMI\Survey\Graph\Repository;

use GraphAware\Neo4j\Client\Client as Neo4jClient;

class Neo4j
{
    protected $client;

    public function __construct(Neo4jClient $client)
    {
        $this->client = $client;
    }
}
