<?php

return [
    'layer' => env('DB_LAYER_CONNECTION', 'layer'),

    'connections' => [
        env('DB_LAYER_CONNECTION', 'layer') => [
            'driver' => env('DB_LAYER_DRIVER', env('DB_DRIVER', 'pgsql')),
            'url' => env('DB_LAYER_URL', env('DB_URL')),
            'host' => env('DB_LAYER_HOST', env('DB_HOST')),
            'port' => env('DB_LAYER_PORT', env('DB_PORT')),
            'database' => env('DB_LAYER_DATABASE', env('DB_DATABASE')),
            'username' => env('DB_LAYER_USERNAME', env('DB_USERNAME')),
            'password' => env('DB_LAYER_PASSWORD', env('DB_PASSWORD')),
            'charset' => env('DB_LAYER_CHARSET', env('DB_CHARSET', 'utf8')),
            'prefix' => env('DB_LAYER_PREFIX', env('DB_PREFIX', '')),
            'prefix_indexes' => true,
            'schema' => env('DB_LAYER_SCHEMA', env('DB_SCHEMA', 'public')),
            'sslmode' => env('DB_LAYER_SSLMODE', env('DB_SSLMODE', 'prefer')),
        ],
    ],

    'layersStruct' => [
        env('LAYER_ROOT', 'core') => [
            env('LAYER_MAIN', 'main') => true,
        ]
    ]

];
