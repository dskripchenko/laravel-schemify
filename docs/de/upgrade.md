# Upgrade-Anleitung

## 2.x → 3.0

Version 3.0 modernisiert das Paket (PHP 8.2–8.5, Laravel 11–13) und ändert drei
Dinge, die ein Eingreifen erfordern.

### 1. Migrationen — keine Kopien je Layer mehr

**Vorher:** Jeder Layer hatte unter `database/migrations/<layer>/` eine eigene
Kopie jeder Migration, erzeugt von `layers:install`.

**Jetzt:** Ein gemeinsamer Satz Tenant-Migrationen läuft gegen jeden Layer.

1. Führen Sie Ihre Layer-Migrationen in einem einzigen Tenant-Verzeichnis
   zusammen (Standard `database/migrations/tenant`) und behalten Sie je eine
   Kopie:

   ```bash
   mkdir -p database/migrations/tenant
   # die maßgeblichen Kopien verschieben; die Verzeichnisse database/migrations/<layer>/ löschen
   ```

2. Zentrale (nicht mandantenbezogene) Migrationen bleiben in
   `database/migrations`.

3. Liegt der Ordner woanders, geben Sie den Pfad an:

   ```php
   // config/schemify.php
   'migrations' => [
       'path' => database_path('migrations/tenant'),
   ],
   ```

`migrate --layer=<name>` wendet nun `schemify.migrations.path` auf das Schema
dieses Layers an. `migrate --layer=core` (der zentrale Layer) nutzt
`database/migrations`.

### 2. Verschlüsselte Zugangsdaten

`db_connections.password` wird jetzt **verschlüsselt** gespeichert, die Spalte
ist vom Typ `text`.

- Stellen Sie sicher, dass `APP_KEY` gesetzt ist.
- Speichern Sie bestehende Verbindungen erneut, damit die Werte verschlüsselt
  werden:

  ```php
  use Dskripchenko\Schemify\Models\DbConnection;

  DbConnection::withTrashed()->get()->each(function ($c) {
      $c->password = /* aktuelles Klartext-Passwort */;
      $c->save();
  });
  ```

  (Für eine Neuinstallation die neue Migration veröffentlichen:
  `php artisan vendor:publish --tag=schemify-migrations`.)

### 3. Installationsbefehl

`layers:install` kopiert keine Migrationen mehr. Der Befehl veröffentlicht jetzt
Konfiguration und Kern-Migration, migriert den zentralen Layer und registriert
den Standard-Layer. Führen Sie ihn erneut aus:

```bash
php artisan layers:install
```

### Konfiguration

Veröffentlichen und prüfen Sie die neue `config/schemify.php`:

```bash
php artisan vendor:publish --tag=schemify-config
```

Der Name des zentralen Layers steht nicht mehr fest im Code, sondern ist über
`schemify.central_layer` konfigurierbar (Standard `core`).

### Entfernte APIs

- `InstallMigrationsCommand` (abstrakt) sowie die Hilfsmethoden zum Kopieren von
  Migrationen an `BaseCommand` (`copyMigrations`, `getMigrationFilePathMap`, …).
- `LayerItem::getLayerItemByName()` liefert jetzt `?ConnectorInterface` — fehlt
  ein Layer, kommt `null` zurück statt einer Exception.
