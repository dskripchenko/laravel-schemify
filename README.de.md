# dskripchenko/laravel-schemify

> 🌐 [English](README.md) · [Русский](README.ru.md) · **Deutsch** · [中文](README.zh.md)

Dynamische **Multi-Schema-PostgreSQL**-Verbindungen für Laravel. Schemify bildet
jedes PostgreSQL-Schema als *Layer* ab (eine `layer_items`-Zeile, die auf eine
`db_connections`-Zeile verweist) und ergänzt die Laravel-Befehle `migrate`,
`db:seed` und `db:wipe` um eine `--layer`-Option, damit du sie im laufenden
Betrieb gegen das Schema eines beliebigen Layers ausführen kannst. Layer können
in derselben Datenbank liegen (verschiedene Schemas) oder in völlig
unterschiedlichen Datenbanken.

[![Packagist](https://img.shields.io/packagist/v/dskripchenko/laravel-schemify)](https://packagist.org/packages/dskripchenko/laravel-schemify)
[![Tests](https://github.com/dskripchenko/laravel-schemify/actions/workflows/tests.yml/badge.svg)](https://github.com/dskripchenko/laravel-schemify/actions/workflows/tests.yml)
[![License](https://img.shields.io/packagist/l/dskripchenko/laravel-schemify)](LICENSE.md)

> Schemify ist ein leichtgewichtiger Schema-/Verbindungs-Umschalter, der in die
> Migrationsbefehle eingebunden ist — **kein** vollständiges Tenancy-Framework.
> Wenn du automatische Mandantenerkennung, Cache-/Queue-/Filesystem-Isolierung
> oder Row-Scoping innerhalb einer einzelnen DB brauchst, greif zu
> [stancl/tenancy](https://tenancyforlaravel.com/) oder
> [spatie/laravel-multitenancy](https://github.com/spatie/laravel-multitenancy).

## Unterstützte Versionen

| | Versionen |
|---|---|
| PHP | 8.2 · 8.3 · 8.4 · 8.5 |
| Laravel | 11 · 12 · 13 |
| Datenbank | PostgreSQL |

## Installation

```bash
composer require dskripchenko/laravel-schemify
php artisan layers:install
```

Der Provider wird automatisch erkannt. `layers:install` veröffentlicht die
Konfiguration und die Core-Migration, erstellt die Tabellen `db_connections` /
`layer_items` auf der zentralen Verbindung und registriert den Standard-Layer
`main`.

## Kurzüberblick

```bash
php artisan layers:new acme --schema=acme --migrate   # create a layer + schema, run tenant migrations
php artisan migrate --layer=acme                      # migrate a single layer
php artisan layers:list                               # list layers
```

```php
use Dskripchenko\Schemify\Facades\Schemify;

$total = Schemify::use('acme', fn () => Invoice::sum('amount'));
```

## Dokumentation

- [Erste Schritte](docs/de/getting-started.md)
- [Befehle](docs/de/commands.md)
- [Layer-Umschaltung zur Laufzeit](docs/de/runtime.md)
- [Migrationen](docs/de/migrations.md)
- [Sicherheit](docs/de/security.md)

Du steigst von 2.x um? Siehe [UPGRADE.md](UPGRADE.md) und das
[CHANGELOG](CHANGELOG.md).

## Lizenz

[MIT](LICENSE.md) © Denis Skripchenko
