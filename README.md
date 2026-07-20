# laravel-schemify

Dynamic **multi-schema PostgreSQL** connections for Laravel. Schemify models
each PostgreSQL schema as a *layer* (a `layer_items` row pointing at a
`db_connections` row) and adds a `--layer` option to Laravel's `migrate`,
`db:seed` and `db:wipe` commands so you can run them against any layer's schema
on the fly. Layers can live in the same database (different schemas) or in
entirely different databases.

> Schemify is a lightweight schema/connection switcher wired into the migration
> commands — **not** a full tenancy framework. If you need automatic tenant
> identification, cache/queue/filesystem isolation or single-DB row scoping,
> reach for [stancl/tenancy](https://tenancyforlaravel.com/) or
> [spatie/laravel-multitenancy](https://github.com/spatie/laravel-multitenancy).

## Supported versions

| | Versions |
|---|---|
| PHP | 8.2 · 8.3 · 8.4 · 8.5 |
| Laravel | 11 · 12 · 13 |
| Database | PostgreSQL |

## Installation

```bash
composer require dskripchenko/laravel-schemify
php artisan layers:install
```

The provider is auto-discovered. `layers:install` publishes the config and core
migration, creates the `db_connections` / `layer_items` tables on the central
connection, and registers the default `main` layer.

## Concepts

- **Central layer** (`schemify.central_layer`, default `core`) — runs against
  the app's default connection with **no** schema switch. Its migrations live
  in `database/migrations`. This is where the package's own tables live.
- **Tenant layers** — each is a named schema (optionally on its own
  connection). A **single shared** migration set (`schemify.migrations.path`,
  default `database/migrations/tenant`) is run against every layer — nothing is
  copied per layer.

## Usage

### Manage layers

```bash
php artisan layers:new acme --schema=acme --migrate   # create layer + schema, run tenant migrations
php artisan layers:list                               # show layers and where they point
php artisan layers:delete acme --drop-schema          # remove layer (and optionally DROP the schema)
```

### Per-layer migration / database commands

Every migration / database command accepts `--layer`:

```bash
php artisan migrate --layer=acme
php artisan migrate:fresh --layer=acme
php artisan migrate:rollback --layer=acme
php artisan migrate:status --layer=acme
php artisan db:seed --layer=acme
php artisan db:wipe --layer=acme

php artisan layers:migrate    # run tenant migrations across every enabled layer in layersStruct
```

`--layer=core` (the central layer) runs against the default connection.

### Runtime layer switching

```php
use Dskripchenko\Schemify\Facades\Schemify;

// Run a closure with a layer active; the previous layer is restored afterwards.
$total = Schemify::use('acme', fn () => Invoice::sum('amount'));

Schemify::switchTo('acme');   // switch for the rest of the request
Schemify::current();          // 'acme'
Schemify::forget();           // back to the default connection
```

### Models bound to a layer

```php
use Dskripchenko\Schemify\Traits\DynamicConnectionTrait;

class Report extends Model
{
    use DynamicConnectionTrait;

    // Used when no layer is active at runtime.
    public function getLayerItemName(): string
    {
        return 'main';
    }
}
```

When a layer is active (`Schemify::use()` / `switchTo()`), models using the
trait follow it automatically.

## Configuration

`config/schemify.php` (published by `layers:install`):

```php
'connection'    => env('DB_LAYER_CONNECTION', 'layer'),   // connection reconfigured per layer
'central_layer' => env('SCHEMIFY_CENTRAL_LAYER', 'core'), // non-tenant layer
'migrations'    => [
    'path'         => database_path('migrations/tenant'), // shared tenant migrations
    'central_path' => database_path('migrations'),
],
```

The `layer` connection template and the `layersStruct` hierarchy live in
`config/database.php` (merged in by the package).

## Security notes

- `db_connections.password` is stored **encrypted** (Laravel `encrypted` cast) —
  requires `APP_KEY`.
- Schema names are validated and double-quoted before reaching `CREATE SCHEMA` /
  `DROP SCHEMA`.

## Development

```bash
composer install
composer test       # phpunit — Postgres integration tests self-skip if no DB
composer analyse    # phpstan
composer format     # pint
```

Integration tests use env `SCHEMIFY_TEST_DB_*` (falling back to `DB_*`, then to
`127.0.0.1:5432/schemify_test`).

## Upgrading

See [UPGRADE.md](UPGRADE.md) for 2.x → 3.0 (breaking: migration model, config,
encrypted credentials).

## License

MIT — see [LICENSE.md](LICENSE.md).
