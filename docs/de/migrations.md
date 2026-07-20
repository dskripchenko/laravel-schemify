---
title: Migrationen
locale: de
status: stable
---

# Migrationen

Schemify führt ein **einziges gemeinsames Set** an Mandanten-Migrationen gegen
das Schema jedes Layers aus — nichts wird pro Layer kopiert.

## Verzeichnisaufbau

```
database/
  migrations/            ← central migrations (run on --layer=core)
    2020_..._create_schemify_core_tables.php
    ...                  ← your app-wide / central tables
  migrations/tenant/     ← tenant migrations (run against each layer's schema)
    2024_..._create_invoices_table.php
    ...
```

- **Zentraler Pfad** (`schemify.migrations.central_path`, Standard
  `database/migrations`) wird für `--layer=<central_layer>` verwendet.
- **Mandanten-Pfad** (`schemify.migrations.path`, Standard
  `database/migrations/tenant`) wird für jeden anderen Layer verwendet.

Konfiguriere beide in `config/schemify.php`:

```php
'migrations' => [
    'path'         => database_path('migrations/tenant'),
    'central_path' => database_path('migrations'),
],
```

## Migrationen ausführen

```bash
# central tables (default connection, no schema switch)
php artisan migrate --layer=core

# a single tenant layer
php artisan migrate --layer=acme

# every enabled layer in layersStruct
php artisan layers:migrate
```

`migrate --layer=acme` schaltet die Verbindung auf das Schema `acme` um (und
erstellt es bei Bedarf) und wendet dort das Mandanten-Migrations-Set an.
`migrate:fresh`, `migrate:rollback`, `migrate:reset` und `migrate:status`
verhalten sich pro Layer auf die gleiche Weise.

### Den Pfad überschreiben

Die Standardoption `--path` gewinnt weiterhin, wenn du einen einmaligen Ort
brauchst:

```bash
php artisan migrate --layer=acme --path=database/migrations/special
```

## Wie es aufgelöst wird (`PathByLayer`)

1. ein explizites `--path` wird wortwörtlich verwendet;
2. andernfalls `--layer == central_layer` → der zentrale Pfad;
3. andernfalls → der Mandanten-Pfad.

## Umstieg von Kopien pro Layer (2.x)

Vor 3.0 hatte jeder Layer seine eigene Kopie jeder Migration unter
`database/migrations/<layer>/`. Verschiebe die kanonischen Kopien in einen
einzigen Ordner `database/migrations/tenant/` und lösche die Duplikate pro
Layer. Siehe [UPGRADE.md](../../UPGRADE.md).

## Siehe auch

- [Befehle](commands.md)
- [Erste Schritte](getting-started.md)
