---
title: Erste Schritte
locale: de
status: stable
---

# Erste Schritte

`dskripchenko/laravel-schemify` gibt einer Laravel-Anwendung dynamische,
pro-*Layer* aufgebaute PostgreSQL-Verbindungen. Ein **Layer** ist ein
benanntes Schema (optional auf einer eigenen Datenbankverbindung); Schemify
schaltet die aktive Verbindung bei Bedarf auf dieses Schema um und lehrt den
Migrationsbefehlen eine `--layer`-Option bei.

## Voraussetzungen

- PHP 8.2–8.5
- Laravel 11 / 12 / 13
- PostgreSQL
- Gesetzter `APP_KEY` (Datenbank-Zugangsdaten werden verschlüsselt gespeichert)

## Installation

```bash
composer require dskripchenko/laravel-schemify
php artisan layers:install
```

Der Service Provider wird automatisch erkannt. `layers:install` ist idempotent
und:

1. veröffentlicht `config/schemify.php` und die Core-Migration,
2. migriert den **zentralen** Layer (und erstellt dabei die Tabellen
   `db_connections` und `layer_items`),
3. registriert den Standard-Layer `main`.

Ergänze `--force`, um veröffentlichte Dateien zu überschreiben und Rückfragen zu
überspringen (nützlich in CI / bei Deployments):

```bash
php artisan layers:install --force
```

## Kernkonzepte

### Layer

Jeder Layer ist eine Zeile in `layer_items`:

| Spalte | Bedeutung |
|---|---|
| `name` | eindeutiger Layer-Name (wird mit `--layer` verwendet) |
| `schema_name` | das PostgreSQL-Schema, auf das er abgebildet wird |
| `layer` | ein Gruppen-/Typ-Tag (ermöglicht `getAllLayerItems($type)`, ein Set abzurufen) |
| `db_connection_id` | die `db_connections`-Zeile, auf der er liegt |

Eine `db_connections`-Zeile enthält Treiber/Host/Port/Datenbank/Benutzername/Passwort
des Servers, der das Schema hostet — so können verschiedene Layer auf völlig
unterschiedliche Datenbanken verweisen.

### Zentrale vs. Mandanten-Layer

- **Zentraler Layer** (`schemify.central_layer`, Standard `core`) läuft gegen die
  Standardverbindung der Anwendung **ohne** Schema-Umschaltung. Seine Migrationen
  liegen in `database/migrations`. Die eigenen Tabellen des Pakets liegen hier.
- **Mandanten-Layer** sind die benannten Schemas. Ein einziges gemeinsames Set an
  Mandanten-Migrationen wird gegen jeden von ihnen ausgeführt — siehe
  [Migrationen](migrations.md).

## Konfiguration

`config/schemify.php` (veröffentlicht durch `layers:install`):

```php
return [
    'connection'    => env('DB_LAYER_CONNECTION', 'layer'),
    'central_layer' => env('SCHEMIFY_CENTRAL_LAYER', 'core'),
    'migrations'    => [
        'path'         => database_path('migrations/tenant'),
        'central_path' => database_path('migrations'),
    ],
];
```

Das `layer`-Verbindungstemplate und die `layersStruct`-Hierarchie liegen in
`config/database.php` (vom Paket eingemischt). `layersStruct` ist eine
verschachtelte Map aktivierter Layer, die von `layers:migrate` verwendet wird:

```php
'layersStruct' => [
    'core' => [
        'main' => true,
    ],
],
```

## Nächste Schritte

- [Befehle](commands.md) — die vollständige Artisan-Oberfläche
- [Layer-Umschaltung zur Laufzeit](runtime.md) — die `Schemify`-Facade und Models
- [Migrationen](migrations.md) — das Modell mit einem einzigen Migrations-Set
- [Sicherheit](security.md) — verschlüsselte Zugangsdaten und Schema-Namen-Sicherheit
