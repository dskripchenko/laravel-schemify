---
title: Переключение слоёв во время выполнения
locale: ru
status: stable
---

# Переключение слоёв во время выполнения

Вне консоли переключайте слои с помощью фасада `Schemify` (за которым стоит
`Dskripchenko\Schemify\Support\SchemifyManager`, привязанный как синглтон
`schemify`).

## Фасад `Schemify`

```php
use Dskripchenko\Schemify\Facades\Schemify;

// Выполнить колбэк с активным слоем; предыдущий слой восстанавливается
// после — даже если колбэк выбросил исключение.
$total = Schemify::use('acme', fn () => Invoice::sum('amount'));

// Переключиться на остаток запроса.
Schemify::switchTo('acme');
Schemify::current();   // 'acme'

// Вернуться к соединению по умолчанию.
Schemify::forget();
```

| метод | описание |
|---|---|
| `use(string $name, Closure $cb)` | активирует `$name`, выполняет `$cb`, восстанавливает предыдущий слой; возвращает значение `$cb` |
| `switchTo(string $name): ConnectionInterface` | делает `$name` активным и возвращает его соединение; выбрасывает `InvalidArgumentException`, если слой неизвестен |
| `current(): ?string` | имя активного слоя или `null` на соединении по умолчанию |
| `connectionName(): string` | имя соединения, переконфигурируемого под каждый слой |
| `forget(): void` | сбрасывает активный слой и очищает соединение слоя |

## Модели, привязанные к слою

Добавьте трейт `DynamicConnectionTrait` к модели, чтобы её соединение следовало
за Schemify:

```php
use Dskripchenko\Schemify\Traits\DynamicConnectionTrait;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use DynamicConnectionTrait;

    // Используется, когда во время выполнения нет активного слоя.
    public function getLayerItemName(): string
    {
        return 'main';
    }
}
```

Порядок разрешения соединения модели:

1. слой, активированный через `Schemify::use()` / `switchTo()`, если таковой есть;
2. иначе `getLayerItemName()`.

```php
Schemify::use('acme', function () {
    Report::create([...]);   // записывается в схему 'acme'
});

Report::count();             // использует getLayerItemName() → 'main'
```

## Провижининг слоёв в рантайме (v3.1)

`layers:new` / `layers:delete` — тонкие обёртки над менеджером; те же операции
доступны программно (например, из админ-контроллера):

```php
use Dskripchenko\Schemify\Facades\Schemify;

$layer = Schemify::provision('acme', schema: 'acme', group: 'workspace', migrate: true);
Schemify::deprovision('acme', dropSchema: true);
```

`provision()` валидирует имя схемы, регистрирует слой (клонируя дефолтное
подключение, если не передан `connectionId`), создаёт схему и — при
`migrate: true` — прогоняет tenant-миграции. Активный до вызова слой
сохраняется. Оба метода бросают `InvalidArgumentException` при некорректном входе.

## События (v3.1)

- `Dskripchenko\Schemify\Events\LayerSwitched` — после каждого `switchTo()`
  (`previous`, `current`).
- `Dskripchenko\Schemify\Events\LayerForgotten` — после `forget()` (`previous`).

Используйте их для пере-скоупа зависимой от слоя инфраструктуры, например
кэш-префикса:

```php
Event::listen(LayerSwitched::class, function (LayerSwitched $e) {
    config(['cache.prefix' => 'app:'.$e->current]);
    Cache::forgetDriver();
});
```

## Проброс в очередь (v3.1)

Включите `schemify.queue.propagate` (env `SCHEMIFY_QUEUE_PROPAGATE=true`) — job,
поставленный при активном слое, понесёт его в payload; воркер переключится на
этот слой перед выполнением и восстановит прежнее состояние после (безопасно и
для `sync`-драйвера). Job, чей слой уже удалён, падает с ошибкой — осознанно.

## Примечания

- `switchTo()` переконфигурирует единственное общее соединение
  (`schemify.connection`). В рамках одного запроса рассматривайте переключение
  слоёв как последовательное — не удерживайте живые построители запросов из двух
  слоёв одновременно.
- `ConnectionHelper::needToReconnect()` пропускает избыточные переподключения,
  когда целевые опции не изменились.

## Смотрите также

- [Команды](commands.md)
- [Безопасность](security.md)
