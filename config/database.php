<?php

return [
    'schemify' => env('DB_SCHEMIFY_CONNECTION', 'schemify'),

    'connections' => [
        env('DB_SCHEMIFY_CONNECTION', 'schemify') => [
            'driver' => 'pgsql',
            'url' => env('DB_SCHEMIFY_URL', env('DATABASE_URL')),
            'host' => env('DB_SCHEMIFY_HOST', env('DB_HOST')),
            'port' => env('DB_SCHEMIFY_PORT', env('DB_PORT')),
            'database' => env('DB_SCHEMIFY_DATABASE', env('DB_DATABASE')),
            'username' => env('DB_SCHEMIFY_USERNAME', env('DB_USERNAME')),
            'password' => env('DB_SCHEMIFY_PASSWORD', env('DB_PASSWORD')),
            'charset' => env('DB_SCHEMIFY_CHARSET', env('DB_CHARSET', 'utf8')),
            'prefix' => env('DB_SCHEMIFY_PREFIX', env('DB_PREFIX', '')),
            'prefix_indexes' => true,
            'schema' => env('DB_SCHEMIFY_SCHEMA', env('DB_SCHEMA', 'public')),
            'sslmode' => env('DB_SCHEMIFY_SSLMODE', env('DB_SSLMODE', 'prefer')),
        ],
    ]
];
