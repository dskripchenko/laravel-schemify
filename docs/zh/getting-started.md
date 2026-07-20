---
title: 快速开始
locale: zh
status: stable
---

# 快速开始

`dskripchenko/laravel-schemify` 为 Laravel 应用提供动态的、按*层*划分的
PostgreSQL 连接。一个**层**（layer）就是一个具名模式（可选择性地位于其自身的
数据库连接上）；Schemify 会按需将活动连接切换到该模式，并为迁移命令添加
`--layer` 选项。

## 环境要求

- PHP 8.2–8.5
- Laravel 11 / 12 / 13
- PostgreSQL
- 已设置 `APP_KEY`（数据库凭据以加密方式存储）

## 安装

```bash
composer require dskripchenko/laravel-schemify
php artisan layers:install
```

服务提供者会被自动发现。`layers:install` 是幂等的，它会：

1. 发布 `config/schemify.php` 和核心迁移，
2. 迁移**中央**层（创建 `db_connections` 和 `layer_items` 表），
3. 注册默认的 `main` 层。

添加 `--force` 可覆盖已发布的文件并跳过确认提示（在 CI / 部署中很有用）：

```bash
php artisan layers:install --force
```

## 核心概念

### 层（Layers）

每个层都是 `layer_items` 表中的一条记录：

| 列 | 含义 |
|---|---|
| `name` | 唯一的层名称（配合 `--layer` 使用） |
| `schema_name` | 它映射到的 PostgreSQL 模式 |
| `layer` | 分组/类型标签（让 `getAllLayerItems($type)` 可以获取一组层） |
| `db_connection_id` | 它所在的 `db_connections` 记录 |

`db_connections` 记录保存着承载该模式的服务器的
driver/host/port/database/username/password——因此不同的层可以指向完全不同的
数据库。

### 中央层与租户层

- **中央层**（`schemify.central_layer`，默认为 `core`）针对应用的默认连接运行，
  **不**进行模式切换。它的迁移位于 `database/migrations` 中。本包自身的表就位于
  这里。
- **租户层**是那些具名的模式。一套共享的租户迁移会针对每一个租户层运行——参见
  [迁移](migrations.md)。

## 配置

`config/schemify.php`（由 `layers:install` 发布）：

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

`layer` 连接模板和 `layersStruct` 层级结构位于 `config/database.php` 中（由本包
合并进去）。`layersStruct` 是一个由已启用层组成的嵌套映射，供 `layers:migrate`
使用：

```php
'layersStruct' => [
    'core' => [
        'main' => true,
    ],
],
```

## 下一步

- [命令](commands.md) —— 完整的 artisan 命令集
- [运行时层切换](runtime.md) —— `Schemify` 门面与模型
- [迁移](migrations.md) —— 单套迁移模型
- [安全](security.md) —— 加密凭据与模式名安全
