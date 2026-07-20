---
title: Команды
locale: ru
status: stable
---

# Команды

## Управление слоями

### `layers:install`

Инициализирует пакет: публикует ассеты, мигрирует центральный слой,
регистрирует слой по умолчанию. Идемпотентна.

```bash
php artisan layers:install [--force]
```

### `layers:new`

Регистрирует новый слой и создаёт его PostgreSQL-схему.

```bash
php artisan layers:new acme
php artisan layers:new acme --schema=acme_prod --layer=customers --migrate
php artisan layers:new acme --connection=3          # переиспользовать существующий id из db_connections
```

| опция | по умолчанию | значение |
|---|---|---|
| `--schema=` | имя слоя | создаваемая схема |
| `--layer=` | имя слоя | тег группы/типа |
| `--connection=` | — | переиспользовать существующий id из `db_connections` вместо клонирования соединения по умолчанию |
| `--migrate` | выкл. | сразу выполнить тенантные миграции для нового слоя |
| `--force` | выкл. | пропустить подтверждения |

Имена схем валидируются (буквы, цифры, подчёркивание; должны начинаться с
буквы/подчёркивания; ≤ 63 символов) до запуска какого-либо SQL.

### `layers:list`

Показывает зарегистрированные слои и то, куда указывает каждый из них.

```bash
php artisan layers:list
```

### `layers:delete`

Удаляет слой из реестра. С флагом `--drop-schema` также удаляет
PostgreSQL-схему (деструктивно, `CASCADE`).

```bash
php artisan layers:delete acme
php artisan layers:delete acme --drop-schema --force
```

## Команды миграций и базы данных в разрезе слоёв

Каждая команда миграции / базы данных принимает `--layer`:

```bash
php artisan migrate --layer=acme
php artisan migrate:fresh --layer=acme
php artisan migrate:rollback --layer=acme
php artisan migrate:reset --layer=acme
php artisan migrate:status --layer=acme
php artisan db:seed --layer=acme
php artisan db:wipe --layer=acme
```

- `--layer=core` (центральный слой) работает против соединения по умолчанию без
  переключения схемы.
- Любое другое значение разрешает слой по имени; если слоя с таким именем не
  существует, команда вместо этого перебирает каждый слой этой **группы**
  (столбец `layer`).

### `layers:migrate`

Выполняет тенантные миграции по **всем включённым слоям** в `layersStruct`:

```bash
php artisan layers:migrate
```

## Смотрите также

- [Миграции](migrations.md)
- [Переключение слоёв во время выполнения](runtime.md)
