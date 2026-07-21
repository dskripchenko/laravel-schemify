<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Layer connection
    |--------------------------------------------------------------------------
    | Name of the database connection Schemify reconfigures on the fly for the
    | active layer. Its template lives in config/database.php under the same
    | key and is merged into database.connections by the package.
    */
    'connection' => env('DB_LAYER_CONNECTION', 'layer'),

    /*
    |--------------------------------------------------------------------------
    | Central layer
    |--------------------------------------------------------------------------
    | The "central" (non-tenant) layer. When a command is run with
    | --layer=<central_layer> it executes against the default connection with
    | no schema switch — this is where the package's own tables (db_connections,
    | layer_items) and any app-wide tables live.
    */
    'central_layer' => env('SCHEMIFY_CENTRAL_LAYER', env('LAYER_ROOT', 'core')),

    /*
    |--------------------------------------------------------------------------
    | Migrations
    |--------------------------------------------------------------------------
    | A SINGLE shared set of tenant migrations is run against every layer's
    | schema — nothing is copied per layer. `path` is where those migrations
    | live; `central_path` is used for the central layer.
    */
    'migrations' => [
        'path' => env('SCHEMIFY_TENANT_MIGRATIONS_PATH', database_path('migrations/tenant')),
        'central_path' => database_path('migrations'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue propagation
    |--------------------------------------------------------------------------
    | When enabled, jobs dispatched while a layer is active carry that layer in
    | their payload; workers switch to it before running the job and restore
    | the previous state afterwards. Opt-in — see Queue\LayerPropagator.
    */
    'queue' => [
        'propagate' => env('SCHEMIFY_QUEUE_PROPAGATE', false),
    ],
];
