---
title: Befehle
locale: de
status: stable
---

# Befehle

## Layer verwalten

### `layers:install`

Bootstrap des Pakets: Assets veröffentlichen, den zentralen Layer migrieren, den
Standard-Layer registrieren. Idempotent.

```bash
php artisan layers:install [--force]
```

### `layers:new`

Einen neuen Layer registrieren und sein PostgreSQL-Schema erstellen.

```bash
php artisan layers:new acme
php artisan layers:new acme --schema=acme_prod --layer=customers --migrate
php artisan layers:new acme --connection=3          # reuse an existing db_connections id
```

| Option | Standard | Bedeutung |
|---|---|---|
| `--schema=` | Layer-Name | zu erstellendes Schema |
| `--layer=` | Layer-Name | Gruppen-/Typ-Tag |
| `--connection=` | — | eine bestehende `db_connections`-id wiederverwenden, statt die Standardverbindung zu klonen |
| `--migrate` | aus | die Mandanten-Migrationen für den neuen Layer sofort ausführen |
| `--force` | aus | Bestätigungen überspringen |

Schema-Namen werden validiert (Buchstaben, Ziffern, Unterstrich; müssen mit einem
Buchstaben/Unterstrich beginnen; ≤ 63 Zeichen), bevor irgendein SQL ausgeführt
wird.

### `layers:list`

Registrierte Layer anzeigen und worauf jeder verweist.

```bash
php artisan layers:list
```

### `layers:delete`

Einen Layer aus der Registry entfernen. Mit `--drop-schema` wird zusätzlich das
PostgreSQL-Schema gelöscht (destruktiv, `CASCADE`).

```bash
php artisan layers:delete acme
php artisan layers:delete acme --drop-schema --force
```

## Migrations- & Datenbankbefehle pro Layer

Jeder Migrations-/Datenbankbefehl akzeptiert `--layer`:

```bash
php artisan migrate --layer=acme
php artisan migrate:fresh --layer=acme
php artisan migrate:rollback --layer=acme
php artisan migrate:reset --layer=acme
php artisan migrate:status --layer=acme
php artisan db:seed --layer=acme
php artisan db:wipe --layer=acme
```

- `--layer=core` (der zentrale Layer) läuft gegen die Standardverbindung ohne
  Schema-Umschaltung.
- Jeder andere Wert löst einen Layer über seinen Namen auf; existiert kein Layer
  mit diesem Namen, iteriert der Befehl stattdessen über jeden Layer dieser
  **Gruppe** (Spalte `layer`).

### `layers:migrate`

Mandanten-Migrationen über **jeden aktivierten Layer** in `layersStruct`
ausführen:

```bash
php artisan layers:migrate
```

## Siehe auch

- [Migrationen](migrations.md)
- [Layer-Umschaltung zur Laufzeit](runtime.md)
