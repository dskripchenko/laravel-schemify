---
title: Layer-Umschaltung zur Laufzeit
locale: de
status: stable
---

# Layer-Umschaltung zur Laufzeit

Außerhalb der Konsole schaltest du Layer mit der `Schemify`-Facade um (gestützt
auf `Dskripchenko\Schemify\Support\SchemifyManager`, gebunden als Singleton
`schemify`).

## Die `Schemify`-Facade

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

| Methode | Beschreibung |
|---|---|
| `use(string $name, Closure $cb)` | `$name` aktivieren, `$cb` ausführen, den vorherigen Layer wiederherstellen; gibt den Wert von `$cb` zurück |
| `switchTo(string $name): ConnectionInterface` | `$name` aktiv machen und seine Verbindung zurückgeben; wirft `InvalidArgumentException`, wenn unbekannt |
| `current(): ?string` | der Name des aktiven Layers, oder `null` auf der Standardverbindung |
| `connectionName(): string` | der Verbindungsname, der pro Layer neu konfiguriert wird |
| `forget(): void` | den aktiven Layer verwerfen und die Layer-Verbindung leeren |

## An einen Layer gebundene Models

Füge einem Model das `DynamicConnectionTrait` hinzu, damit seine Verbindung
Schemify folgt:

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

Auflösungsreihenfolge für die Verbindung des Models:

1. der über `Schemify::use()` / `switchTo()` aktive Layer, falls vorhanden;
2. andernfalls `getLayerItemName()`.

```php
Schemify::use('acme', function () {
    Report::create([...]);   // written into the 'acme' schema
});

Report::count();             // uses getLayerItemName() → 'main'
```

## Layer-Provisionierung zur Laufzeit (v3.1)

`layers:new` / `layers:delete` sind dünne Wrapper um den Manager — dieselben
Operationen stehen programmatisch zur Verfügung (z. B. aus einem Admin-Controller):

```php
use Dskripchenko\Schemify\Facades\Schemify;

$layer = Schemify::provision('acme', schema: 'acme', group: 'workspace', migrate: true);
Schemify::deprovision('acme', dropSchema: true);
```

`provision()` validiert den Schema-Namen, registriert den Layer (kloniert die
Default-Verbindung, sofern keine `connectionId` übergeben wird), erstellt das
Schema und führt mit `migrate: true` die Tenant-Migrationen aus. Der zuvor
aktive Layer bleibt erhalten. Beide Methoden werfen bei ungültiger Eingabe eine
`InvalidArgumentException`.

## Events (v3.1)

- `Dskripchenko\Schemify\Events\LayerSwitched` — nach jedem `switchTo()`
  (`previous`, `current`).
- `Dskripchenko\Schemify\Events\LayerForgotten` — nach `forget()` (`previous`).

Damit lässt sich layer-abhängige Infrastruktur neu scopen, z. B. ein
Cache-Präfix pro Layer:

```php
Event::listen(LayerSwitched::class, function (LayerSwitched $e) {
    config(['cache.prefix' => 'app:'.$e->current]);
    Cache::forgetDriver();
});
```

## Queue-Propagation (v3.1)

Mit aktiviertem `schemify.queue.propagate` (env `SCHEMIFY_QUEUE_PROPAGATE=true`)
tragen Jobs, die bei aktivem Layer dispatcht werden, diesen im Payload; der
Worker wechselt vor der Ausführung auf den Layer und stellt danach den vorigen
Zustand wieder her (auch mit dem `sync`-Driver sicher). Ein Job, dessen Layer
nicht mehr existiert, schlägt bewusst laut fehl.

## Hinweise

- `switchTo()` konfiguriert eine einzige gemeinsame Verbindung neu
  (`schemify.connection`). Behandle die Layer-Umschaltung innerhalb einer
  Anfrage als sequenziell — halte nicht gleichzeitig aktive Query-Builder aus
  zwei Layern.
- `ConnectionHelper::needToReconnect()` überspringt überflüssige
  Neuverbindungen, wenn die Zieloptionen unverändert sind.

## Siehe auch

- [Befehle](commands.md)
- [Sicherheit](security.md)
