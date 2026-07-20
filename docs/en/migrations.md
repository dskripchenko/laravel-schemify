---
title: Migrations
locale: en
status: stable
---

# Migrations

Schemify runs a **single shared set** of tenant migrations against every
layer's schema — nothing is copied per layer.

## Directory layout

```
database/
  migrations/            ← central migrations (run on --layer=core)
    2020_..._create_schemify_core_tables.php
    ...                  ← your app-wide / central tables
  migrations/tenant/     ← tenant migrations (run against each layer's schema)
    2024_..._create_invoices_table.php
    ...
```

- **Central path** (`schemify.migrations.central_path`, default
  `database/migrations`) is used for `--layer=<central_layer>`.
- **Tenant path** (`schemify.migrations.path`, default
  `database/migrations/tenant`) is used for every other layer.

Configure either in `config/schemify.php`:

```php
'migrations' => [
    'path'         => database_path('migrations/tenant'),
    'central_path' => database_path('migrations'),
],
```

## Running migrations

```bash
# central tables (default connection, no schema switch)
php artisan migrate --layer=core

# a single tenant layer
php artisan migrate --layer=acme

# every enabled layer in layersStruct
php artisan layers:migrate
```

`migrate --layer=acme` switches the connection to the `acme` schema (creating it
if needed) and applies the tenant migration set there. `migrate:fresh`,
`migrate:rollback`, `migrate:reset` and `migrate:status` behave the same way per
layer.

### Overriding the path

The standard `--path` option still wins when you need a one-off location:

```bash
php artisan migrate --layer=acme --path=database/migrations/special
```

## How it resolves (`PathByLayer`)

1. an explicit `--path` is used verbatim;
2. otherwise, `--layer == central_layer` → the central path;
3. otherwise → the tenant path.

## Migrating from per-layer copies (2.x)

Before 3.0 each layer had its own copy of every migration under
`database/migrations/<layer>/`. Move the canonical copies into a single
`database/migrations/tenant/` folder and delete the per-layer duplicates. See
[UPGRADE.md](../../UPGRADE.md).

## See also

- [Commands](commands.md)
- [Getting started](getting-started.md)
