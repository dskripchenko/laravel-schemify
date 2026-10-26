---
title: Runtime layer switching
locale: en
status: stable
---

# Runtime layer switching

Outside of the console, switch layers with the `Schemify` facade (backed by
`Dskripchenko\Schemify\Support\SchemifyManager`, bound as the `schemify`
singleton).

## The `Schemify` facade

```php
use Dskripchenko\Schemify\Facades\Schemify;

// Run a callback with a layer active; the previous layer is restored
// afterwards — even if the callback throws.
$total = Schemify::use('acme', fn () => Invoice::sum('amount'));

// Switch for the rest of the request.
Schemify::switchTo('acme');
Schemify::current();   // 'acme'

// Back to the default connection.
Schemify::forget();
```

| method | description |
|---|---|
| `use(string $name, Closure $cb)` | activate `$name`, run `$cb`, restore the previous layer; returns `$cb`'s value |
| `switchTo(string $name): ConnectionInterface` | make `$name` active and return its connection; throws `InvalidArgumentException` if unknown |
| `current(): ?string` | the active layer name, or `null` on the default connection |
| `connectionName(): string` | the connection name reconfigured per layer |
| `forget(): void` | drop the active layer and purge the layer connection |

## Models bound to a layer

Add the `DynamicConnectionTrait` to a model so its connection follows Schemify:

```php
use Dskripchenko\Schemify\Traits\DynamicConnectionTrait;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use DynamicConnectionTrait;

    // Used when no layer is active at runtime.
    public function getLayerItemName(): string
    {
        return 'main';
    }
}
```

Resolution order for the model's connection:

1. the layer active via `Schemify::use()` / `switchTo()`, if any;
2. otherwise `getLayerItemName()`.

```php
Schemify::use('acme', function () {
    Report::create([...]);   // written into the 'acme' schema
});

Report::count();             // uses getLayerItemName() → 'main'
```

## Provisioning layers at runtime (v3.1)

`layers:new` / `layers:delete` are thin wrappers around the manager — the same
operations are available programmatically (e.g. from an admin controller):

```php
use Dskripchenko\Schemify\Facades\Schemify;

$layer = Schemify::provision('acme', schema: 'acme', group: 'workspace', migrate: true);
Schemify::deprovision('acme', dropSchema: true);
```

`provision()` validates the schema name, registers the layer (cloning the
default DB connection unless `connectionId` is given), creates the schema and —
with `migrate: true` — runs the tenant migration set. The previously active
layer is preserved. Both methods throw `InvalidArgumentException` on bad input.

## Events (v3.1)

- `Dskripchenko\Schemify\Events\LayerSwitched` — after every `switchTo()`
  (`previous`, `current`).
- `Dskripchenko\Schemify\Events\LayerForgotten` — after `forget()`
  (`previous`).

Use them to re-scope layer-dependent infrastructure, e.g. a per-layer cache
prefix:

```php
Event::listen(LayerSwitched::class, function (LayerSwitched $e) {
    config(['cache.prefix' => 'app:'.$e->current]);
    Cache::forgetDriver();
});
```

## Queue propagation (v3.1)

Enable `schemify.queue.propagate` (env `SCHEMIFY_QUEUE_PROPAGATE=true`) and
jobs dispatched while a layer is active will carry it in their payload; a
worker switches to that layer before running the job and restores the previous
state afterwards (safe for the `sync` driver too). A job whose layer no longer
exists fails loudly — by design.

## Behind a transaction pooler (v3.5)

A switch in the default `session` scope runs `SET search_path`, which lives as
long as the server connection. Behind PgBouncer in **transaction** mode the
server connection goes to another client after every transaction — and the
tenant's path goes with it. Use the transaction scope there:

```php
// config/schemify.php
'search_path' => [
    'scope' => 'transaction',
    'append' => ['extensions'],          // schemas every layer needs after its own
    'direct' => ['port' => 5432],        // console runs bypass the pooler
],
```

- A switch only records the layer. Every transaction on the layer connection
  starts with `set_config('search_path', …, true)` — the `SET LOCAL` form —
  so the path dies with the transaction.
- A query on a connection with an active layer **outside a transaction** throws
  `OutsideTransactionException`: it would run with whatever path the pooled
  server connection has. Make the transaction the unit of work:

  ```php
  Schemify::transaction('acme', fn () => Invoice::sum('amount'));
  ```

  In an HTTP application, a middleware that switches the layer and wraps the
  request in `DB::transaction()` does the same for every request.
- `migrate`, `db:seed` and `db:wipe` with `--layer` run on a plain session (the
  migrator reads its table outside transactions), with `direct` merged into the
  connection options so they go past the pooler. The connection is reset to
  its pooled template afterwards.
- The connection template must not set `search_path` / `schema`: in this scope
  they are removed, because Laravel's connector would issue a session-level
  `SET` on connect. Give the database itself the default path
  (`ALTER DATABASE … SET search_path = public, extensions`).
- PDO's named prepared statements do not survive transaction pooling either:
  set `Pdo\Pgsql::ATTR_DISABLE_PREPARES` (PHP 8.4+; `PDO::PGSQL_ATTR_DISABLE_PREPARES`
  before) in the connection's `options`.

### The registry on its own connection

When the layer connection **is** the default connection (the whole request
works in one tenant), point the registry elsewhere so layer lookups do not
follow the switched tenant:

```php
'central_connection' => 'central',
```

`LayerItem` and `DbConnection` then use that connection.

## Notes

- `switchTo()` reconfigures a single shared connection (`schemify.connection`).
  Within one request, treat layer switching as sequential — don't hold live
  query builders from two layers at the same time.
- `ConnectionHelper::needToReconnect()` skips redundant reconnects when the
  target options are unchanged.

## See also

- [Commands](commands.md)
- [Security](security.md)
