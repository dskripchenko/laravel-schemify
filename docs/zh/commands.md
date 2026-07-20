---
title: 命令
locale: zh
status: stable
---

# 命令

## 管理层

### `layers:install`

引导初始化本包：发布资源、迁移中央层、注册默认层。幂等。

```bash
php artisan layers:install [--force]
```

### `layers:new`

注册一个新层并创建其 PostgreSQL 模式。

```bash
php artisan layers:new acme
php artisan layers:new acme --schema=acme_prod --layer=customers --migrate
php artisan layers:new acme --connection=3          # reuse an existing db_connections id
```

| 选项 | 默认值 | 含义 |
|---|---|---|
| `--schema=` | 层名称 | 要创建的模式 |
| `--layer=` | 层名称 | 分组/类型标签 |
| `--connection=` | — | 复用现有的 `db_connections` id，而不是克隆默认连接 |
| `--migrate` | 关闭 | 立即为新层运行租户迁移 |
| `--force` | 关闭 | 跳过确认提示 |

在执行任何 SQL 之前，模式名会先经过校验（字母、数字、下划线；必须以字母/下划线
开头；≤ 63 个字符）。

### `layers:list`

显示已注册的层以及各自指向何处。

```bash
php artisan layers:list
```

### `layers:delete`

从注册表中移除一个层。加上 `--drop-schema` 还会一并删除该 PostgreSQL 模式
（破坏性操作，`CASCADE`）。

```bash
php artisan layers:delete acme
php artisan layers:delete acme --drop-schema --force
```

## 按层执行的迁移与数据库命令

每个迁移 / 数据库命令都接受 `--layer`：

```bash
php artisan migrate --layer=acme
php artisan migrate:fresh --layer=acme
php artisan migrate:rollback --layer=acme
php artisan migrate:reset --layer=acme
php artisan migrate:status --layer=acme
php artisan db:seed --layer=acme
php artisan db:wipe --layer=acme
```

- `--layer=core`（中央层）针对默认连接运行，不进行模式切换。
- 任何其他值都会按名称解析一个层；如果不存在该名称的层，命令会转而遍历该
  **分组**（`layer` 列）中的每一个层。

### `layers:migrate`

针对 `layersStruct` 中**每一个已启用的层**运行租户迁移：

```bash
php artisan layers:migrate
```

## 另请参阅

- [迁移](migrations.md)
- [运行时层切换](runtime.md)
