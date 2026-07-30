# Changelog

## 3.4.1

### Performance
- **`forget()` больше не разрывает соединение.** Он делал `DB::purge()`, и из-за
  этого экономия 3.4.0 не наступала нигде: следующее переключение всё равно
  подключалось заново. Воркер очереди открывал соединение на каждую задачу.

  Теперь подключение возвращается к исходной схеме через `SET search_path`, а
  рвётся только если вернуться нельзя (нестандартный путь из нескольких схем).
  Оставаться на схеме клиента после `forget()` недопустимо, поэтому это
  проверяется тестом: код, взявший подключение слоя без переключения, не должен
  видеть таблицы последнего клиента.

## 3.4.0

### Performance
- **Смена слоя больше не пересоздаёт соединение.** `reconnect()` делал
  `DB::purge()` и подключался заново на каждом переключении — новый TCP и новая
  аутентификация. Когда слой меняется на каждом HTTP-запросе, это заметно:
  профилирование приложения-потребителя показало **12% времени запроса** на
  установку соединений и **два соединения к Postgres на запрос**.

  Теперь, если изменилась только схема и соединение уже установлено,
  выполняется `SET search_path`. Полное переподключение остаётся там, где оно
  действительно нужно — когда слой живёт на другом сервере (`db_connections`).

  Изоляция не ослабевает: `search_path` выставляется в одну схему, без
  `public`, ровно как при подключении заново. Покрыто тестом, который пишет
  одноимённые таблицы в двух слоях и читает их через одно соединение.

- **`CREATE SCHEMA IF NOT EXISTS` не повторяется на каждом переключении.**
  Это DDL, он берёт блокировку, а схема создаётся при провижининге слоя.
  Подтверждённое существование запоминается на процесс;
  `ConnectionHelper::forgetEnsuredSchemas()` сбрасывает подтверждение и
  вызывается при удалении слоя.

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
