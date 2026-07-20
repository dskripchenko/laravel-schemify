# Upgrade guide

## 2.x → 3.0

3.0 modernizes the package (PHP 8.2–8.5, Laravel 11–13) and changes three
things that need action.

### 1. Migration layout — no more per-layer copies

**Before:** each layer had its own copy of every migration under
`database/migrations/<layer>/`, produced by `layers:install`.

**Now:** one shared set of tenant migrations is run against every layer.

1. Move your per-layer migrations into a single tenant folder
   (default `database/migrations/tenant`), keeping one copy of each:

   ```bash
   mkdir -p database/migrations/tenant
   # move the canonical copies; delete the duplicated database/migrations/<layer>/ folders
   ```

2. Keep central (non-tenant) migrations in `database/migrations`.

3. Configure the path if you use a different location:

   ```php
   // config/schemify.php
   'migrations' => [
       'path' => database_path('migrations/tenant'),
   ],
   ```

Running `migrate --layer=<name>` now applies `schemify.migrations.path` against
that layer's schema. `migrate --layer=core` (the central layer) uses
`database/migrations`.

### 2. Encrypted credentials

`db_connections.password` is now stored **encrypted** and the column is `text`.

- Ensure `APP_KEY` is set.
- Re-save existing connections so the values are encrypted, e.g.:

  ```php
  use Dskripchenko\Schemify\Models\DbConnection;

  DbConnection::withTrashed()->get()->each(function ($c) {
      $c->password = /* current plaintext password */;
      $c->save();
  });
  ```

  (Publish the new migration for a fresh install with
  `php artisan vendor:publish --tag=schemify-migrations`.)

### 3. Install command

`layers:install` no longer copies migrations. It now publishes config + the core
migration, migrates the central layer, and registers the default layer. Re-run:

```bash
php artisan layers:install
```

### Config

Publish and review the new `config/schemify.php`:

```bash
php artisan vendor:publish --tag=schemify-config
```

The central layer name is now configurable (`schemify.central_layer`, default
`core`) instead of hardcoded.

### Removed APIs

- `InstallMigrationsCommand` (abstract) and the migration-copy helpers on
  `BaseCommand` (`copyMigrations`, `getMigrationFilePathMap`, …).
- `LayerItem::getLayerItemByName()` now returns `?ConnectorInterface` (returns
  `null` instead of throwing when a layer is missing).
