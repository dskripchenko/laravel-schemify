# dskripchenko/laravel-schemify

> 🌐 **English** · [Русский](README.ru.md) · [Deutsch](README.de.md) · [中文](README.zh.md)

Dynamic **multi-schema PostgreSQL** connections for Laravel. Schemify models
each PostgreSQL schema as a *layer* (a `layer_items` row pointing at a
`db_connections` row) and adds a `--layer` option to Laravel's `migrate`,
`db:seed` and `db:wipe` commands so you can run them against any layer's schema
on the fly. Layers can live in the same database (different schemas) or in
entirely different databases.

[![Packagist](https://img.shields.io/packagist/v/dskripchenko/laravel-schemify)](https://packagist.org/packages/dskripchenko/laravel-schemify)
[![Tests](https://github.com/dskripchenko/laravel-schemify/actions/workflows/tests.yml/badge.svg)](https://github.com/dskripchenko/laravel-schemify/actions/workflows/tests.yml)
[![License](https://img.shields.io/packagist/l/dskripchenko/laravel-schemify)](LICENSE.md)

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

## Install

```bash
composer require dskripchenko/laravel-schemify
php artisan layers:install
```

The provider is auto-discovered. `layers:install` publishes the config and core
migration, creates the `db_connections` / `layer_items` tables on the central
connection, and registers the default `main` layer.

## Quick tour

```bash
php artisan layers:new acme --schema=acme --migrate   # create a layer + schema, run tenant migrations
php artisan migrate --layer=acme                      # migrate a single layer
php artisan layers:list                               # list layers
```

```php
use Dskripchenko\Schemify\Facades\Schemify;

$total = Schemify::use('acme', fn () => Invoice::sum('amount'));
```

## Documentation

- [Getting started](docs/en/getting-started.md)
- [Commands](docs/en/commands.md)
- [Runtime layer switching](docs/en/runtime.md)
- [Migrations](docs/en/migrations.md)
- [Security](docs/en/security.md)

Upgrading from 2.x? See [UPGRADE.md](UPGRADE.md) and the [CHANGELOG](CHANGELOG.md).

## License

[MIT](LICENSE.md) © Denis Skripchenko
