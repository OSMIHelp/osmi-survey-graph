<?php

namespace OSMI\Survey\Graph\DependencyInjection;

use GraphAware\Neo4j\Client\ClientBuilder;
use OSMI\Survey\Graph\Repository\ExtractData;
use OSMI\Survey\Graph\Repository\JsonImport;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class OsmiSurveyGraphProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['neo4j'] = function ($c) {
            return ClientBuilder::create()
                ->addConnection('default', getenv('GRAPH_URL'))
                ->build();
        };

        $pimple['jsonImportRepository'] = function ($c) {
            return new JsonImport($c['neo4j']);
        };

        $pimple['extractDataRepository'] = function ($c) {
            return new ExtractData($c['neo4j']);
        };
    }
}
