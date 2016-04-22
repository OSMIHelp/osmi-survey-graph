<?php

/**
 * OSMI Survey Graph project.
 *
 * @link https://github.com/OSMIHelp/osmi-survey-graph
 */
namespace OSMI\Survey\Graph\Repository;

use GraphAware\Neo4j\Client\Client;

class Neo4j
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * Public constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }
}
