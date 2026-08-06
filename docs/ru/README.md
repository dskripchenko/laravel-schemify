# dskripchenko/laravel-schemify

> 🌐 [English](../../README.md) · **Русский** · [Deutsch](../de/README.md) · [中文](../zh/README.md)

Динамические **мультисхемные PostgreSQL**-соединения для Laravel. Schemify
моделирует каждую PostgreSQL-схему как *слой* (строку `layer_items`,
указывающую на строку `db_connections`) и добавляет опцию `--layer` к командам
Laravel `migrate`, `db:seed` и `db:wipe`, чтобы вы могли запускать их против
схемы любого слоя на лету. Слои могут находиться в одной базе данных (разные
схемы) или в совершенно разных базах данных.

[![Packagist](https://img.shields.io/packagist/v/dskripchenko/laravel-schemify)](https://packagist.org/packages/dskripchenko/laravel-schemify)
[![Tests](https://github.com/dskripchenko/laravel-schemify/actions/workflows/tests.yml/badge.svg)](https://github.com/dskripchenko/laravel-schemify/actions/workflows/tests.yml)
[![License](https://img.shields.io/packagist/l/dskripchenko/laravel-schemify)](../../LICENSE.md)

> Schemify — это лёгкий переключатель схем/соединений, встроенный в команды
> миграций, а **не** полноценный фреймворк мультитенантности. Если вам нужна
> автоматическая идентификация тенанта, изоляция кэша/очереди/файловой системы
> или разграничение строк в рамках одной БД, обратитесь к
> [stancl/tenancy](https://tenancyforlaravel.com/) или
> [spatie/laravel-multitenancy](https://github.com/spatie/laravel-multitenancy).

## Поддерживаемые версии

| | Версии |
|---|---|
| PHP | 8.2 · 8.3 · 8.4 · 8.5 |
| Laravel | 11 · 12 · 13 |
| База данных | PostgreSQL |

## Установка

```bash
composer require dskripchenko/laravel-schemify
php artisan layers:install
```

Провайдер обнаруживается автоматически. `layers:install` публикует конфиг и
базовую миграцию, создаёт таблицы `db_connections` / `layer_items` на
центральном соединении и регистрирует слой `main` по умолчанию.

## Краткий обзор

```bash
php artisan layers:new acme --schema=acme --migrate   # создать слой + схему, выполнить тенантные миграции
php artisan migrate --layer=acme                      # мигрировать один слой
php artisan layers:list                               # список слоёв
```

```php
use Dskripchenko\Schemify\Facades\Schemify;

$total = Schemify::use('acme', fn () => Invoice::sum('amount'));
```

## Документация

- [Начало работы](getting-started.md)
- [Команды](commands.md)
- [Переключение слоёв во время выполнения](runtime.md)
- [Миграции](migrations.md)
- [Безопасность](security.md)

Обновляетесь с 2.x? Смотрите [UPGRADE.md](../UPGRADE.md) и [CHANGELOG](../../CHANGELOG.md).

## Лицензия

[MIT](../../LICENSE.md) © Denis Skripchenko
