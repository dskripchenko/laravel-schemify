# Changelog

## [3.4.2] — 2026-08-05

### Fixed
- **`--layer` was silently missing from half the overridden commands on
  Laravel 13.21+.** The option was declared through `getOptions()`, which
  Laravel only calls for commands without a `$signature`. As the framework
  migrates its own commands to signatures — 13.21 did so for `migrate:fresh`,
  `migrate:install`, `migrate:status`, `migrate:reset`, `db:seed` and
  `db:wipe` — the override stopped existing: the command still resolved to
  this package's class, but `--layer` was gone and passing it aborted the run.
  The option is now added in `configure()`, which the Symfony constructor
  always calls, so it no longer depends on how the parent declares its
  parameters.

- **Schema introspection reported the previous layer after an in-place
  switch (Laravel 11 and 12).** Since 3.4.0 a layer switch reuses the
  connection and only issues `SET search_path`, so the connection instance
  kept the config of the layer before it. Queries were unaffected — they
  follow the session path — but the Postgres schema builder on Laravel 11/12
  takes the schema from the connection config, so `Schema::hasTable()`
  answered about the wrong layer. In practice `migrate:install` found the
  previous layer's `migrations` table, skipped creating one for the current
  layer, and the `migrate` that followed failed on the missing table — so
  `layers:migrate` broke on the second layer onwards. Laravel 13 asks
  `current_schema()` and was never affected.

## [3.4.1] — 2026-07-31

### Performance
- **`forget()` no longer drops the connection.** It called `DB::purge()`, and
  because of that the saving introduced in 3.4.0 never materialized anywhere:
  the next switch reconnected regardless. A queue worker opened a connection
  for every job.

  The connection is now returned to its original schema through
  `SET search_path`, and is torn down only when returning is impossible (a
  non-standard path spanning several schemas). Staying on a tenant's schema
  after `forget()` is unacceptable, so a test guards it: code that takes the
  layer connection without switching must not see the last tenant's tables.

## [3.4.0] — 2026-07-31

### Performance
- **Switching a layer no longer recreates the connection.** `reconnect()`
  called `DB::purge()` and connected anew on every switch — a fresh TCP
  connection and a fresh authentication. When the layer changes on every HTTP
  request that shows: profiling of a consuming application attributed **12% of
  request time** to establishing connections, and **two Postgres connections
  per request**.

  Now, when only the schema changed and the connection is already established,
  a `SET search_path` is issued. A full reconnect remains where it is genuinely
  needed — when the layer lives on another server (`db_connections`).

  Isolation is not weakened: `search_path` is set to a single schema, without
  `public`, exactly as on a fresh connection. Covered by a test that writes
  identically named tables in two layers and reads them through one connection.

- **`CREATE SCHEMA IF NOT EXISTS` is no longer repeated on every switch.** It
  is DDL, it takes a lock, and the schema is created when the layer is
  provisioned. A confirmed existence is remembered per process;
  `ConnectionHelper::forgetEnsuredSchemas()` clears the confirmation and is
  called when a layer is removed.

## 3.3.0

### Fixed (isolation)
- **`search_path` template override could silently break schema switching.**
  The pgsql connector prefers `search_path` over `schema`; if the host app's
  layer-connection template defined `search_path`, Schemify's per-layer
  `schema` merge was ignored and every "switched" query landed in `public`.
  `prepareConnection()` now mirrors the layer schema into `search_path`
  (and `needToReconnect()` accounts for it).

### Changed
- **`layers:migrate` is registry-driven**: iterates layers registered in
  `layer_items` (optionally `--group=`) instead of the legacy config
  `database.layersStruct`; `--force` is passed through to each per-layer
  `migrate`. Intended as the deploy step after the central `migrate`.

## 3.2.0

Integration fixes driven by the first host application. Behavioural — review
before upgrading if you relied on the old quirks.

### Changed
- **`--layer` now defaults to the configured central layer** (was a hardcoded
  `main`): a plain `php artisan migrate` behaves exactly like vanilla Laravel —
  central run on the default connection. Deploy scripts need no changes.
- **Tenant runs use only the shared tenant path.** `migrate --layer=X` no
  longer replays provider-registered migrations (`loadMigrationsFrom` — e.g.
  vendor package tables) into every layer schema; those belong to the central
  run. Central runs keep vanilla path merging.

### Fixed
- Command overrides are now applied via container `extend()` on both the
  legacy `command.*` aliases and the `Illuminate\…\*Command` class abstracts —
  the modern console kernel resolves by class, so the overrides actually take
  effect regardless of provider registration order.
- `migrate:install` (per-layer) crashed on Laravel 11+ where
  `config('database.migrations')` is an array — the table name is now read
  from `migrations.table`.

## 3.1.0

Runtime additions for host applications that manage layers from code (driven by
the first real consumer — a multi-tenant service).

### Added
- **Programmatic provisioning** — `Schemify::provision(name, schema?, group?,
  connectionId?, migrate?)` and `Schemify::deprovision(name, dropSchema?)`;
  `layers:new` / `layers:delete` are now thin wrappers around the manager.
  Provisioning preserves the previously active layer; deprovisioning the
  current layer forgets it first (never "restores" onto a dropped schema).
- **Events** — `Events\LayerSwitched(previous, current)` after every
  `switchTo()` and `Events\LayerForgotten(previous)` after `forget()` — hook
  points for per-layer cache/storage scoping.
- **Queue propagation (opt-in)** — with `schemify.queue.propagate` enabled,
  jobs dispatched while a layer is active carry it in their payload; workers
  switch to it before the job and restore the previous state afterwards
  (`sync`-driver safe; a job whose layer is gone fails loudly).

### Docs
- New runtime sections (provisioning / events / queue) in all four languages.

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

## [2.0.0] - 2021-04-05

### Changed
- Second generation of the package: the layer registry and the migration
  pipeline were rebuilt.

## [1.0.0] - 2020-12-09

### Added
- First release: schema-per-layer multitenancy for Laravel.
