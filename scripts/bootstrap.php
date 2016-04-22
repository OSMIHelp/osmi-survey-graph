<?php

define('APPLICATION_PATH', realpath(dirname(__DIR__)));

require APPLICATION_PATH . '/vendor/autoload.php';

if (file_exists(APPLICATION_PATH . '/.env')) {
    $dotenv = new Dotenv\Dotenv(APPLICATION_PATH);
    // Using overload to overwrite existing environment variables
    $dotenv->overload();
}

if (!defined('APP_MODE')) {
    $mode = getenv('APP_MODE') ? getenv('APP_MODE') : 'production';
    define('APP_MODE', $mode);
}

date_default_timezone_set('UTC');
error_reporting(getenv('PHP_ERROR_REPORTING'));
ini_set('display_errors', getenv('PHP_DISPLAY_ERRORS'));
ini_set('display_startup_errors', getenv('PHP_DISPLAY_STARTUP_ERRORS'));

$container = new \Pimple\Container();
$container->register(new \OSMI\Survey\Graph\DependencyInjection\OsmiSurveyGraphProvider());
