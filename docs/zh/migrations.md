---
title: 迁移
locale: zh
status: stable
---

# 迁移

Schemify 针对每个层的模式运行**一套共享的**租户迁移——不会为每个层复制任何内容。

## 目录布局

```
database/
  migrations/            ← central migrations (run on --layer=core)
    2020_..._create_schemify_core_tables.php
    ...                  ← your app-wide / central tables
  migrations/tenant/     ← tenant migrations (run against each layer's schema)
    2024_..._create_invoices_table.php
    ...
```

- **中央路径**（`schemify.migrations.central_path`，默认为 `database/migrations`）
  用于 `--layer=<central_layer>`。
- **租户路径**（`schemify.migrations.path`，默认为
  `database/migrations/tenant`）用于其他所有层。

两者均可在 `config/schemify.php` 中配置：

```php
'migrations' => [
    'path'         => database_path('migrations/tenant'),
    'central_path' => database_path('migrations'),
],
```

## 运行迁移

```bash
# central tables (default connection, no schema switch)
php artisan migrate --layer=core

# a single tenant layer
php artisan migrate --layer=acme

# every enabled layer in layersStruct
php artisan layers:migrate
```

`migrate --layer=acme` 会将连接切换到 `acme` 模式（如有需要则创建它），并在其上
应用租户迁移集。`migrate:fresh`、`migrate:rollback`、`migrate:reset` 和
`migrate:status` 在按层执行时行为一致。

### 覆盖路径

当你需要一次性指定位置时，标准的 `--path` 选项仍然优先：

```bash
php artisan migrate --layer=acme --path=database/migrations/special
```

## 解析方式（`PathByLayer`）

1. 显式的 `--path` 会被原样使用；
2. 否则，`--layer == central_layer` → 使用中央路径；
3. 否则 → 使用租户路径。

## 从按层复制迁移（2.x）

在 3.0 之前，每个层在 `database/migrations/<layer>/` 下都有一份自己的迁移副本。
请将规范副本移入单一的 `database/migrations/tenant/` 文件夹，并删除各层的重复副本。
参见 [UPGRADE.md](../UPGRADE.md)。

## 另请参阅

- [命令](commands.md)
- [快速开始](getting-started.md)
