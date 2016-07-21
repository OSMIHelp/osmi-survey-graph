<?php

return [
    'settings' => [
        'slimMode' => SLIM_MODE,
        'displayErrorDetails' => false, // Always false because API
        'determineRouteBeforeAppMiddleware' => true,
        // Monolog settings
        'logger' => [
            'app' => [
                'name' => 'app',
                'path' => APPLICATION_PATH . '/log/app.log',
            ],
        ],
        'pdo' => [
        ],
        'doctrine' => [
            'driver' => 'pdo_sqlite',
            'dbname' => 'food_app_api',
            'path' => sprintf(
                APPLICATION_PATH . '/db/food-app-api.sq3'
            ),
            'port' => '',
            'host' => '',
            'username' => '',
            'encoding' => 'utf8',
            'password' => '',
            'options' => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_TIMEOUT => 5,
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            ],
            'proxy_dir' => APPLICATION_PATH . '/cache/Doctrine',
        ],
    ],
];
