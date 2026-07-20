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

## Notes

- `switchTo()` reconfigures a single shared connection (`schemify.connection`).
  Within one request, treat layer switching as sequential — don't hold live
  query builders from two layers at the same time.
- `ConnectionHelper::needToReconnect()` skips redundant reconnects when the
  target options are unchanged.

## See also

- [Commands](commands.md)
- [Security](security.md)
