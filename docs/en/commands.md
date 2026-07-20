---
title: Commands
locale: en
status: stable
---

# Commands

## Managing layers

### `layers:install`

Bootstrap the package: publish assets, migrate the central layer, register the
default layer. Idempotent.

```bash
php artisan layers:install [--force]
```

### `layers:new`

Register a new layer and create its PostgreSQL schema.

```bash
php artisan layers:new acme
php artisan layers:new acme --schema=acme_prod --layer=customers --migrate
php artisan layers:new acme --connection=3          # reuse an existing db_connections id
```

| option | default | meaning |
|---|---|---|
| `--schema=` | layer name | schema to create |
| `--layer=` | layer name | group/type tag |
| `--connection=` | — | reuse an existing `db_connections` id instead of cloning the default connection |
| `--migrate` | off | run tenant migrations for the new layer right away |
| `--force` | off | skip confirmations |

Schema names are validated (letters, digits, underscore; must start with a
letter/underscore; ≤ 63 chars) before any SQL runs.

### `layers:list`

Show registered layers and where each points.

```bash
php artisan layers:list
```

### `layers:delete`

Remove a layer from the registry. With `--drop-schema` it also drops the
PostgreSQL schema (destructive, `CASCADE`).

```bash
php artisan layers:delete acme
php artisan layers:delete acme --drop-schema --force
```

## Per-layer migration & database commands

Every migration / database command accepts `--layer`:

```bash
php artisan migrate --layer=acme
php artisan migrate:fresh --layer=acme
php artisan migrate:rollback --layer=acme
php artisan migrate:reset --layer=acme
php artisan migrate:status --layer=acme
php artisan db:seed --layer=acme
php artisan db:wipe --layer=acme
```

- `--layer=core` (the central layer) runs against the default connection with no
  schema switch.
- Any other value resolves a layer by name; if no layer with that name exists,
  the command iterates every layer of that **group** (`layer` column) instead.

### `layers:migrate`

Run tenant migrations across **every enabled layer** in `layersStruct`:

```bash
php artisan layers:migrate
```

## See also

- [Migrations](migrations.md)
- [Runtime layer switching](runtime.md)
