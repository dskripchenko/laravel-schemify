# Changelog

## 3.0.0

First modernized, stable release. Supported versions: **PHP 8.2–8.5**,
**Laravel 11/12/13**, PostgreSQL.

### ⚠ Breaking changes
- **Migration model:** tenant migrations are no longer copied into
  `database/migrations/<layer>/`. A single shared set
  (`schemify.migrations.path`, default `database/migrations/tenant`) is run
  against each layer; the central layer uses `database/migrations`. See
  [UPGRADE.md](docs/UPGRADE.md).
- **`db_connections.password` is now stored encrypted** (Laravel `encrypted`
  cast, `text` column). Existing plaintext values must be re-saved through the
  model; `APP_KEY` is required.
- **`layers:install` reworked** — it now publishes assets, migrates the central
  layer and registers the default layer instead of copying migrations. The
  abstract `InstallMigrationsCommand` and the copy helpers on `BaseCommand` were
  removed.
- Default layer is no longer seeded by the core migration; it is created by
  `layers:install`.

### Added
- **Management commands:** `layers:new`, `layers:list`, `layers:delete`
  (`--drop-schema`), all with `--force`.
- **Runtime context:** `Schemify` facade / `SchemifyManager` —
  `use()`, `switchTo()`, `current()`, `forget()`; `DynamicConnectionTrait` now
  follows the active layer.
- `config/schemify.php` (central layer, connection, migration paths).
- Configurable central layer (was hardcoded `core`).
- PostgreSQL integration test suite (self-skipping) + Postgres service in CI.

### Security
- Schema names are validated and double-quoted before `CREATE SCHEMA` /
  `DROP SCHEMA` (`Support\SchemaName`).
- Encrypted database credentials (see above).

### Fixed / from the 2.x modernization
- Declared `php`/`laravel` constraints; bumped `laravel-api` `^2.0` → `^5.0`;
  declared `php-array-helper`.
- `MigrationServiceProvider`: pass `Dispatcher` to `MigrateCommand` (L10+) and
  `Migrator` to `FreshCommand` (L9+) — fixes `ArgumentCountError` on modern
  Laravel.
- `ConnectionHelper::reconnect()` implicit-nullable parameter (PHP 8.4/8.5).
- `ApiInstall` calls `layers:migrate` (was the removed `automigrate`).
- `LayerItem::getLayerItemByName()` returns `null` when absent (was throwing),
  matching its consumers; models use `SoftDeletes` and `$fillable`.

### Tooling
- PHPUnit, PHPStan (level 5 + baseline), Pint, `.gitignore`/`.gitattributes`,
  GitHub Actions matrix (PHP 8.2–8.5 × Laravel 11/12/13, EOL-L11 carve-out).
