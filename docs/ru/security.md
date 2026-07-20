---
title: Безопасность
locale: ru
status: stable
---

# Безопасность

## Зашифрованные учётные данные

`db_connections.password` хранится **в зашифрованном виде** через каст
Laravel `encrypted`. Столбец имеет тип `text` (он хранит шифротекст), а чтение
прозрачно расшифровывает значение:

```php
$connection->password = 'secret';   // шифруется при сохранении
$connection->password;              // 'secret' при чтении
```

Для этого необходимо, чтобы `APP_KEY` был задан. Если вы ротируете `APP_KEY`,
перешифруйте существующие строки так же, как Laravel делает это для любого
зашифрованного атрибута.

### Миграция учётных данных из открытого текста (с 2.x)

В 2.x пароль хранился в открытом виде. После обновления пересохраните каждое
соединение, чтобы значение было зашифровано:

```php
use Dskripchenko\Schemify\Models\DbConnection;

DbConnection::withTrashed()->get()->each(function ($c) {
    $c->password = /* текущий пароль в открытом виде */;
    $c->save();
});
```

## Безопасность имён схем

Имена схем попадают в сырой SQL в `CREATE SCHEMA` / `DROP SCHEMA`, поэтому они
никогда не подставляются без проверки. `Dskripchenko\Schemify\Support\SchemaName`:

- **валидирует** по консервативной форме идентификатора — должно начинаться с
  буквы или подчёркивания, затем буквы / цифры / подчёркивания, максимум 63 байта;
- **заключает в двойные кавычки** идентификатор для инструкции.

```php
use Dskripchenko\Schemify\Support\SchemaName;

SchemaName::isValid('acme');                 // true
SchemaName::isValid('a"; DROP SCHEMA x');    // false
SchemaName::quote('acme');                   // "acme"
SchemaName::assertValid('bad name');         // выбрасывает InvalidArgumentException
```

`layers:new` отклоняет невалидный `--schema` до обращения к базе данных, а слой
соединения заключает схему в кавычки при каждом `CREATE SCHEMA IF NOT EXISTS`.

## Учётные данные в `layers:new`

По умолчанию `layers:new` клонирует конфигурацию **соединения по умолчанию**
приложения с базой данных в новую строку `db_connections` (пароль
зашифрован). Передайте `--connection=<id>`, чтобы вместо создания нового
соединения переиспользовать существующее.

## Смотрите также

- [Переключение слоёв во время выполнения](runtime.md)
- [Начало работы](getting-started.md)
