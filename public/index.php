<?php

// Uses environment variables to avoid the need for environment specific config
// files. 12 Factor App FTW OMGWTFBBQ.

define('APPLICATION_PATH', realpath(dirname(__DIR__)));

require APPLICATION_PATH . '/vendor/autoload.php';

use FastRoute\RouteParser\Std as StdParser;
use Hateoas\HateoasBuilder;
use Hateoas\UrlGenerator\CallableUrlGenerator;

// Required for JMS Serializer and HATEOAS libs to work properly
\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader('class_exists');

if (file_exists(APPLICATION_PATH . '/.env')) {
    $dotenv = new Dotenv\Dotenv(APPLICATION_PATH);
    // Using overload to overwrite existing environment variables
    $dotenv->overload();
}

if (!defined('SLIM_MODE')) {
    $mode = getenv('SLIM_MODE') ? getenv('SLIM_MODE') : 'production';
    define('SLIM_MODE', $mode);
}
date_default_timezone_set('UTC');
error_reporting(getenv('OSMI_ERROR_REPORTING'));
ini_set('display_errors', getenv('OSMI_DISPLAY_ERRORS'));
ini_set('display_startup_errors', getenv('OSMI_DISPLAY_STARTUP_ERRORS'));

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file

    $file = __DIR__ . $_SERVER['REQUEST_URI'];
    // strip the query string, if it exists
    if (preg_match("|^([^\?]+)\?(.+)$|", $file, $matches)) {
        $file = $matches[1];
    }
    if (is_file($file)) {
        return false;
    }
}

$settings = require_once APPLICATION_PATH . '/app/settings.php';

// Get the container and register the Slim Auth provider
$container = require_once APPLICATION_PATH . '/app/dependencies.php';

$app = new \Slim\App($container);

// This is happening post-$app instantiation to provide access to the $app instance.
$container['hateoas'] = function ($c) use ($app) {
    return HateoasBuilder::create()
        ->setUrlGenerator(
            null, // By default all links uses the generator configured with the null name
            new CallableUrlGenerator(function ($route, array $parameters, $absolute) use ($app) {
                /*
                 * All of this work is necessary to split the $parameters array
                 * into Slim route named-parameter-data and querystring params.
                 *
                 * It's modeled after the route parsing done in FastRoute
                 * (https://github.com/nikic/FastRoute) which is used by Slim
                 * but not exposed by Slim.
                 */
                $routeParser = new StdParser();
                $slimRouter = $app->getContainer()->get('router');
                $slimRoute = $slimRouter->getNamedRoute($route);
                $pattern = $slimRoute->getPattern();
                $routeDatas = $routeParser->parse($pattern);
                $routeDatas = array_reverse($routeDatas);

                $namedRouteParams = [];

                foreach ($routeDatas as $routeData) {
                    foreach ($routeData as $item) {
                        if (is_array($item)) {
                            $namedRouteParams[$item[0]] = $item[1];
                        }
                    }
                }

                $queryStringParams = array_diff_key($parameters, $namedRouteParams);
                $slimRouteParams = array_intersect_key($parameters, $namedRouteParams);

                return $app->getContainer()
                    ->get('router')
                    ->pathFor($route, $slimRouteParams, $queryStringParams);
            })
        )
        ->build();
};

// Middleware
require APPLICATION_PATH . '/app/middleware.php';

// Routes
require APPLICATION_PATH . '/app/routes/default.php';
require APPLICATION_PATH . '/app/routes/rpc.php';
require APPLICATION_PATH . '/app/routes/rest.php';

// Run!
$app->run();
