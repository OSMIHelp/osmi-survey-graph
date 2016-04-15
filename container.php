<?php

use GraphAware\Neo4j\Client\ClientBuilder;
use Pimple\Container;

$container = new Container();

$container['neo4j'] = function ($c) {
    return ClientBuilder::create()
        ->addConnection('default', getenv('GRAPH_URL'))
        ->build();
};
