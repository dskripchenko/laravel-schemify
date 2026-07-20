# dskripchenko/laravel-schemify

> 🌐 [English](../README.md) · [Русский](README.ru.md) · [Deutsch](README.de.md) · **中文**

为 Laravel 提供动态的**多模式 PostgreSQL**（multi-schema）连接。Schemify 将每个
PostgreSQL 模式建模为一个*层*（layer）——即一条指向 `db_connections` 记录的
`layer_items` 记录——并为 Laravel 的 `migrate`、`db:seed` 和 `db:wipe` 命令添加
`--layer` 选项，让你可以随时针对任意层的模式运行这些命令。各层可以位于同一个
数据库中（不同模式），也可以位于完全不同的数据库中。

[![Packagist](https://img.shields.io/packagist/v/dskripchenko/laravel-schemify)](https://packagist.org/packages/dskripchenko/laravel-schemify)
[![Tests](https://github.com/dskripchenko/laravel-schemify/actions/workflows/tests.yml/badge.svg)](https://github.com/dskripchenko/laravel-schemify/actions/workflows/tests.yml)
[![License](https://img.shields.io/packagist/l/dskripchenko/laravel-schemify)](../LICENSE.md)

> Schemify 是一个接入迁移命令的轻量级模式/连接切换器——它**不是**一个完整的
> 多租户框架。如果你需要自动租户识别、缓存/队列/文件系统隔离，或单库行级作用域，
> 请选用 [stancl/tenancy](https://tenancyforlaravel.com/) 或
> [spatie/laravel-multitenancy](https://github.com/spatie/laravel-multitenancy)。

## 支持的版本

| | 版本 |
|---|---|
| PHP | 8.2 · 8.3 · 8.4 · 8.5 |
| Laravel | 11 · 12 · 13 |
| 数据库 | PostgreSQL |

## 安装

```bash
composer require dskripchenko/laravel-schemify
php artisan layers:install
```

服务提供者会被自动发现。`layers:install` 会发布配置和核心迁移，在中央连接上
创建 `db_connections` / `layer_items` 表，并注册默认的 `main` 层。

## 快速上手

```bash
php artisan layers:new acme --schema=acme --migrate   # create a layer + schema, run tenant migrations
php artisan migrate --layer=acme                      # migrate a single layer
php artisan layers:list                               # list layers
```

```php
use Dskripchenko\Schemify\Facades\Schemify;

$total = Schemify::use('acme', fn () => Invoice::sum('amount'));
```

## 文档

- [快速开始](zh/getting-started.md)
- [命令](zh/commands.md)
- [运行时层切换](zh/runtime.md)
- [迁移](zh/migrations.md)
- [安全](zh/security.md)

从 2.x 升级？请参阅 [UPGRADE.md](UPGRADE.md) 和 [CHANGELOG](../CHANGELOG.md)。

## 许可证

[MIT](../LICENSE.md) © Denis Skripchenko
