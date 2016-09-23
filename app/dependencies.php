<?php

use GraphAware\Neo4j\Client\ClientBuilder;
use Hateoas\Representation\VndErrorRepresentation;
use OSMI\Survey\Graph\Repository\Analysis;
use OSMI\Survey\Graph\Repository\ExtractData;
use OSMI\Survey\Graph\Repository\JsonImport;

// DIC configuration
$container = new \Slim\Container($settings);

$container['neo4j'] = function ($c) {
    $settings = $c->get('settings')['neo4j'];

    return ClientBuilder::create()
        ->addConnection('default', $settings['graphUrl'])
        ->build();
};

$container['jsonImportRepository'] = function ($c) {
    return new JsonImport($c['neo4j']);
};

$container['extractDataRepository'] = function ($c) {
    return new ExtractData($c['neo4j']);
};

$container['analysisRepository'] = function ($c) {
    return new Analysis($c['neo4j']);
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger']['app'];
    $logger = new \Monolog\Logger($settings['name']);
    $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
    $logger->pushProcessor(new \Monolog\Processor\IntrospectionProcessor());
    $logger->pushHandler(
        new \Monolog\Handler\StreamHandler($settings['path'], \Monolog\Logger::DEBUG)
    );

    return $logger;
};

$container['errorHandler'] = function ($c) {
    return function ($request, $response, $exception) use ($c) {
        $c->logger->critical('Unexpected exception causing HTTP 500.', [
            'exception' => $exception,
        ]);

        $error = new VndErrorRepresentation('API BROKEN. SUPPORT NOTIFIED.');
        $json = $c['hateoas']->serialize($error, 'json');

        return $c['response']
            ->withStatus(500)
            ->withHeader('Content-type', 'application/vnd.error+json')
            ->write($json);
    };
};

$container['notFoundHandler'] = function ($c) {
    return function ($request, $response) use ($c) {
        $error = new VndErrorRepresentation('Not Found');
        $json = $c['hateoas']->serialize($error, 'json');

        return $c['response']
            ->withStatus(404)
            ->withHeader('Content-type', 'application/vnd.error+json')
            ->write($json);
    };
};

$container['notAllowedHandler'] = function ($c) {
    return function ($request, $response, $methods) use ($c) {
        $message = 'Method must be one of: ' . implode(', ', $methods);
        $error = new VndErrorRepresentation($message);
        $json = $c['hateoas']->serialize($error, 'json');

        return $c['response']
            ->withStatus(405)
            ->withHeader('Allow', implode(', ', $methods))
            ->withHeader('Content-type', 'application/vnd.error+json')
            ->write($json);
    };
};

$container['halResponse'] = function ($c) {
    return new \OSMI\Survey\Graph\HALResponse($c['hateoas'], $c['settings']['contentType']);
};

return $container;
