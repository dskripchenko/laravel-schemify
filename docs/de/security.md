---
title: Sicherheit
locale: de
status: stable
---

# Sicherheit

## Verschlüsselte Zugangsdaten

`db_connections.password` wird über den `encrypted`-Cast von Laravel
**verschlüsselt** gespeichert. Die Spalte ist `text` (sie enthält Chiffretext),
und beim Lesen wird transparent entschlüsselt:

```php
$connection->password = 'secret';   // encrypted on save
$connection->password;              // 'secret' on read
```

Dies setzt voraus, dass `APP_KEY` gesetzt ist. Wenn du `APP_KEY` rotierst,
verschlüssele bestehende Zeilen genauso neu, wie Laravel es für jedes
verschlüsselte Attribut tut.

### Klartext-Zugangsdaten migrieren (von 2.x)

2.x speicherte das Passwort im Klartext. Speichere nach dem Upgrade jede
Verbindung erneut, damit der Wert verschlüsselt wird:

```php
use Dskripchenko\Schemify\Models\DbConnection;

DbConnection::withTrashed()->get()->each(function ($c) {
    $c->password = /* current plaintext password */;
    $c->save();
});
```

## Schema-Namen-Sicherheit

Schema-Namen gelangen in `CREATE SCHEMA` / `DROP SCHEMA` in rohes SQL, daher
werden sie niemals ungeprüft interpoliert.
`Dskripchenko\Schemify\Support\SchemaName`:

- **validiert** gegen eine konservative Bezeichner-Form — muss mit einem
  Buchstaben oder Unterstrich beginnen, dann Buchstaben / Ziffern /
  Unterstriche, maximal 63 Bytes;
- **setzt** den Bezeichner für das Statement **in doppelte Anführungszeichen**.

```php
use Dskripchenko\Schemify\Support\SchemaName;

SchemaName::isValid('acme');                 // true
SchemaName::isValid('a"; DROP SCHEMA x');    // false
SchemaName::quote('acme');                   // "acme"
SchemaName::assertValid('bad name');         // throws InvalidArgumentException
```

`layers:new` lehnt ein ungültiges `--schema` ab, bevor die Datenbank berührt
wird, und die Verbindungsschicht setzt das Schema bei jedem
`CREATE SCHEMA IF NOT EXISTS` in Anführungszeichen.

## Zugangsdaten in `layers:new`

Standardmäßig klont `layers:new` die Konfiguration der **Standard**-
Datenbankverbindung der Anwendung in eine neue `db_connections`-Zeile (Passwort
verschlüsselt). Übergib `--connection=<id>`, um eine bestehende Verbindung
wiederzuverwenden, statt eine neue zu erstellen.

## Siehe auch

- [Layer-Umschaltung zur Laufzeit](runtime.md)
- [Erste Schritte](getting-started.md)
