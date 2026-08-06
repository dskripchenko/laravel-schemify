# Руководство по обновлению

## 2.x → 3.0

Версия 3.0 модернизирует пакет (PHP 8.2–8.5, Laravel 11–13) и меняет три вещи,
которые требуют вашего вмешательства.

### 1. Раскладка миграций — копий на каждый слой больше нет

**Было:** у каждого слоя лежала своя копия каждой миграции в
`database/migrations/<layer>/`, их создавала `layers:install`.

**Стало:** один общий набор миграций тенанта прогоняется по всем слоям.

1. Сведите миграции слоёв в один каталог тенанта (по умолчанию
   `database/migrations/tenant`), оставив по одной копии каждой:

   ```bash
   mkdir -p database/migrations/tenant
   # перенесите канонические копии; каталоги database/migrations/<layer>/ удалите
   ```

2. Центральные (не тенантные) миграции оставьте в `database/migrations`.

3. Если каталог у вас другой, укажите путь:

   ```php
   // config/schemify.php
   'migrations' => [
       'path' => database_path('migrations/tenant'),
   ],
   ```

Теперь `migrate --layer=<name>` применяет `schemify.migrations.path` к схеме
этого слоя. `migrate --layer=core` (центральный слой) берёт
`database/migrations`.

### 2. Шифрование учётных данных

`db_connections.password` теперь хранится **зашифрованным**, тип колонки — `text`.

- Убедитесь, что `APP_KEY` задан.
- Пересохраните существующие подключения, чтобы значения зашифровались:

  ```php
  use Dskripchenko\Schemify\Models\DbConnection;

  DbConnection::withTrashed()->get()->each(function ($c) {
      $c->password = /* текущий пароль открытым текстом */;
      $c->save();
  });
  ```

  (Для установки с нуля опубликуйте новую миграцию:
  `php artisan vendor:publish --tag=schemify-migrations`.)

### 3. Команда установки

`layers:install` больше не копирует миграции. Теперь она публикует конфиг и
миграцию ядра, мигрирует центральный слой и регистрирует слой по умолчанию.
Запустите повторно:

```bash
php artisan layers:install
```

### Конфигурация

Опубликуйте и просмотрите новый `config/schemify.php`:

```bash
php artisan vendor:publish --tag=schemify-config
```

Имя центрального слоя больше не зашито в код — оно настраивается через
`schemify.central_layer`, по умолчанию `core`.

### Удалённые API

- `InstallMigrationsCommand` (абстрактный) и помощники копирования миграций у
  `BaseCommand` (`copyMigrations`, `getMigrationFilePathMap` и прочие).
- `LayerItem::getLayerItemByName()` теперь возвращает `?ConnectorInterface` —
  когда слоя нет, отдаёт `null`, а не бросает исключение.
