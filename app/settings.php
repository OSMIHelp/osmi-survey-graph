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
        'neo4j' => [
            'graphUrl' => getenv('GRAPH_URL'),
        ],
        'contentType' => 'application/vnd.osmi-v1+json',
    ],
];
