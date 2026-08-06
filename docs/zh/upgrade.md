# 升级指南

## 2.x → 3.0

3.0 对本包做了现代化（PHP 8.2–8.5、Laravel 11–13），并改动了三处需要你动手的地方。

### 1. 迁移布局——不再为每个 layer 保留副本

**过去：** 每个 layer 在 `database/migrations/<layer>/` 下都有一份自己的迁移副本，
由 `layers:install` 生成。

**现在：** 一套共享的租户迁移会对每个 layer 依次执行。

1. 把各 layer 的迁移合并到一个租户目录（默认 `database/migrations/tenant`），
   每个迁移只保留一份：

   ```bash
   mkdir -p database/migrations/tenant
   # 移入权威副本；删除重复的 database/migrations/<layer>/ 目录
   ```

2. 中央（非租户）迁移仍然留在 `database/migrations`。

3. 若你使用其他位置，请配置路径：

   ```php
   // config/schemify.php
   'migrations' => [
       'path' => database_path('migrations/tenant'),
   ],
   ```

现在 `migrate --layer=<name>` 会把 `schemify.migrations.path` 应用到该 layer 的
schema 上；`migrate --layer=core`（中央 layer）则使用 `database/migrations`。

### 2. 凭据加密

`db_connections.password` 现在**加密**存储，列类型为 `text`。

- 确认已设置 `APP_KEY`。
- 重新保存已有连接，让取值完成加密：

  ```php
  use Dskripchenko\Schemify\Models\DbConnection;

  DbConnection::withTrashed()->get()->each(function ($c) {
      $c->password = /* 当前的明文密码 */;
      $c->save();
  });
  ```

  （全新安装请发布新的迁移：
  `php artisan vendor:publish --tag=schemify-migrations`。）

### 3. 安装命令

`layers:install` 不再复制迁移。它现在会发布配置与核心迁移、迁移中央 layer，
并注册默认 layer。请重新执行：

```bash
php artisan layers:install
```

### 配置

发布并检查新的 `config/schemify.php`：

```bash
php artisan vendor:publish --tag=schemify-config
```

中央 layer 的名称不再写死在代码里，改为通过 `schemify.central_layer` 配置，
默认值为 `core`。

### 已移除的 API

- `InstallMigrationsCommand`（抽象类），以及 `BaseCommand` 上用于复制迁移的辅助方法
  （`copyMigrations`、`getMigrationFilePathMap` 等）。
- `LayerItem::getLayerItemByName()` 现在返回 `?ConnectorInterface`——layer 不存在时
  返回 `null`，而不是抛异常。
