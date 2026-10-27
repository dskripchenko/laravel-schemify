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
        // Where package migrations (loadMigrationsFrom) run: 'central',
        // 'tenant' or 'both'.
        'registered' => env('SCHEMIFY_REGISTERED_MIGRATIONS', 'central'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Central connection
    |--------------------------------------------------------------------------
    | The connection of the layer registry (db_connections, layer_items). Null
    | means the default connection. Set it when the layer connection IS the
    | default one, so registry lookups do not follow a switched tenant.
    */
    'central_connection' => env('SCHEMIFY_CENTRAL_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | search_path scope
    |--------------------------------------------------------------------------
    | 'session'     — a switch runs SET search_path on the connection (default).
    | 'transaction' — a switch is applied with SET LOCAL at the start of each
    |                 transaction, and queries outside a transaction on an
    |                 active layer are refused. Required behind PgBouncer in
    |                 transaction mode, where a session SET leaks to other
    |                 clients. Console runs (migrate/seed --layer) use a
    |                 session; 'direct' overrides the connection options of
    |                 every session-path layer connection, e.g.
    |                 ['port' => 5432] to bypass the pooler.
    | 'append'      — schemas added after the layer's own, e.g. ['extensions'].
    */
    'search_path' => [
        'scope' => env('SCHEMIFY_SEARCH_PATH_SCOPE', 'session'),
        'append' => [],
        'direct' => [],
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
