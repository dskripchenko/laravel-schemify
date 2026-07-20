---
title: Миграции
locale: ru
status: stable
---

# Миграции

Schemify выполняет **единый общий набор** тенантных миграций против схемы
каждого слоя — ничего не копируется отдельно для каждого слоя.

## Структура каталогов

```
database/
  migrations/            ← центральные миграции (выполняются при --layer=core)
    2020_..._create_schemify_core_tables.php
    ...                  ← ваши общесистемные / центральные таблицы
  migrations/tenant/     ← тенантные миграции (выполняются против схемы каждого слоя)
    2024_..._create_invoices_table.php
    ...
```

- **Центральный путь** (`schemify.migrations.central_path`, по умолчанию
  `database/migrations`) используется для `--layer=<central_layer>`.
- **Тенантный путь** (`schemify.migrations.path`, по умолчанию
  `database/migrations/tenant`) используется для любого другого слоя.

Настройте любой из них в `config/schemify.php`:

```php
'migrations' => [
    'path'         => database_path('migrations/tenant'),
    'central_path' => database_path('migrations'),
],
```

## Запуск миграций

```bash
# центральные таблицы (соединение по умолчанию, без переключения схемы)
php artisan migrate --layer=core

# один тенантный слой
php artisan migrate --layer=acme

# каждый включённый слой в layersStruct
php artisan layers:migrate
```

`migrate --layer=acme` переключает соединение на схему `acme` (создавая её при
необходимости) и применяет там набор тенантных миграций. `migrate:fresh`,
`migrate:rollback`, `migrate:reset` и `migrate:status` ведут себя так же в
разрезе слоёв.

### Переопределение пути

Стандартная опция `--path` по-прежнему имеет приоритет, когда нужно указать
разовое расположение:

```bash
php artisan migrate --layer=acme --path=database/migrations/special
```

## Как это разрешается (`PathByLayer`)

1. явный `--path` используется дословно;
2. иначе, `--layer == central_layer` → центральный путь;
3. иначе → тенантный путь.

## Переход с копий по слоям (2.x)

До 3.0 у каждого слоя была собственная копия каждой миграции в
`database/migrations/<layer>/`. Перенесите канонические копии в единую папку
`database/migrations/tenant/` и удалите дубликаты по слоям. Смотрите
[UPGRADE.md](../UPGRADE.md).

## Смотрите также

- [Команды](commands.md)
- [Начало работы](getting-started.md)
