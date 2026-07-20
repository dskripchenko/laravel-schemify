---
title: Getting Started
locale: en
status: stable
---

# Getting Started

`dskripchenko/laravel-schemify` gives a Laravel app dynamic, per-*layer*
PostgreSQL connections. A **layer** is a named schema (optionally on its own
database connection); Schemify switches the active connection to that schema on
demand and teaches the migration commands a `--layer` option.

## Requirements

- PHP 8.2–8.5
- Laravel 11 / 12 / 13
- PostgreSQL
- `APP_KEY` set (database credentials are stored encrypted)

## Install

```bash
composer require dskripchenko/laravel-schemify
php artisan layers:install
```

The service provider is auto-discovered. `layers:install` is idempotent and:

1. publishes `config/schemify.php` and the core migration,
2. migrates the **central** layer (creating the `db_connections` and
   `layer_items` tables),
3. registers the default `main` layer.

Add `--force` to overwrite published files and skip prompts (useful in CI /
deploys):

```bash
php artisan layers:install --force
```

## Core concepts

### Layers

Each layer is a row in `layer_items`:

| column | meaning |
|---|---|
| `name` | unique layer name (used with `--layer`) |
| `schema_name` | the PostgreSQL schema it maps to |
| `layer` | a group/type tag (lets `getAllLayerItems($type)` fetch a set) |
| `db_connection_id` | the `db_connections` row it lives on |

A `db_connections` row holds the driver/host/port/database/username/password of
the server that hosts the schema — so different layers can point at different
databases entirely.

### Central vs tenant layers

- **Central layer** (`schemify.central_layer`, default `core`) runs against the
  app's default connection with **no** schema switch. Its migrations live in
  `database/migrations`. The package's own tables live here.
- **Tenant layers** are the named schemas. A single shared set of tenant
  migrations is run against each of them — see [Migrations](migrations.md).

## Configure

`config/schemify.php` (published by `layers:install`):

```php
return [
    'connection'    => env('DB_LAYER_CONNECTION', 'layer'),
    'central_layer' => env('SCHEMIFY_CENTRAL_LAYER', 'core'),
    'migrations'    => [
        'path'         => database_path('migrations/tenant'),
        'central_path' => database_path('migrations'),
    ],
];
```

The `layer` connection template and the `layersStruct` hierarchy live in
`config/database.php` (merged in by the package). `layersStruct` is a nested map
of enabled layers used by `layers:migrate`:

```php
'layersStruct' => [
    'core' => [
        'main' => true,
    ],
],
```

## Next steps

- [Commands](commands.md) — the full artisan surface
- [Runtime layer switching](runtime.md) — the `Schemify` facade and models
- [Migrations](migrations.md) — the single-set migration model
- [Security](security.md) — encrypted credentials and schema-name safety
