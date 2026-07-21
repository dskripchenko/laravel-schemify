---
title: 运行时层切换
locale: zh
status: stable
---

# 运行时层切换

在控制台之外，使用 `Schemify` 门面来切换层（其底层为
`Dskripchenko\Schemify\Support\SchemifyManager`，以 `schemify` 单例形式绑定）。

## `Schemify` 门面

```php
use Dskripchenko\Schemify\Facades\Schemify;

// Run a callback with a layer active; the previous layer is restored
// afterwards — even if the callback throws.
$total = Schemify::use('acme', fn () => Invoice::sum('amount'));

// Switch for the rest of the request.
Schemify::switchTo('acme');
Schemify::current();   // 'acme'

// Back to the default connection.
Schemify::forget();
```

| 方法 | 说明 |
|---|---|
| `use(string $name, Closure $cb)` | 激活 `$name`，运行 `$cb`，然后恢复之前的层；返回 `$cb` 的值 |
| `switchTo(string $name): ConnectionInterface` | 使 `$name` 成为活动层并返回其连接；若未知则抛出 `InvalidArgumentException` |
| `current(): ?string` | 当前活动的层名称，若处于默认连接则为 `null` |
| `connectionName(): string` | 每个层所重新配置的连接名称 |
| `forget(): void` | 丢弃当前活动的层并清除该层的连接 |

## 绑定到某个层的模型

为模型添加 `DynamicConnectionTrait`，使其连接跟随 Schemify：

```php
use Dskripchenko\Schemify\Traits\DynamicConnectionTrait;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use DynamicConnectionTrait;

    // Used when no layer is active at runtime.
    public function getLayerItemName(): string
    {
        return 'main';
    }
}
```

模型连接的解析顺序：

1. 通过 `Schemify::use()` / `switchTo()` 激活的层（如果有）；
2. 否则使用 `getLayerItemName()`。

```php
Schemify::use('acme', function () {
    Report::create([...]);   // written into the 'acme' schema
});

Report::count();             // uses getLayerItemName() → 'main'
```

## 运行时层供给（v3.1）

`layers:new` / `layers:delete` 只是管理器的轻量封装——同样的操作也可以
以编程方式调用（例如在管理后台控制器中）：

```php
use Dskripchenko\Schemify\Facades\Schemify;

$layer = Schemify::provision('acme', schema: 'acme', group: 'workspace', migrate: true);
Schemify::deprovision('acme', dropSchema: true);
```

`provision()` 会校验模式名、注册层（未传入 `connectionId` 时克隆默认数据库
连接）、创建模式，并在 `migrate: true` 时执行租户迁移集。调用前处于激活状态
的层会被保留。两个方法在输入非法时都会抛出 `InvalidArgumentException`。

## 事件（v3.1）

- `Dskripchenko\Schemify\Events\LayerSwitched` —— 每次 `switchTo()` 之后
  （`previous`、`current`）。
- `Dskripchenko\Schemify\Events\LayerForgotten` —— `forget()` 之后（`previous`）。

可以用它们重新设定依赖于层的基础设施，例如按层设置缓存前缀：

```php
Event::listen(LayerSwitched::class, function (LayerSwitched $e) {
    config(['cache.prefix' => 'app:'.$e->current]);
    Cache::forgetDriver();
});
```

## 队列传播（v3.1）

启用 `schemify.queue.propagate`（env `SCHEMIFY_QUEUE_PROPAGATE=true`）后，
在某个层激活时派发的任务会在 payload 中携带该层；worker 在执行任务前切换到
该层，执行结束后恢复先前状态（对 `sync` 驱动同样安全）。若任务的层已被删除，
任务会显式失败——这是有意为之。

## 注意事项

- `switchTo()` 会重新配置单个共享连接（`schemify.connection`）。在同一个请求内，
  应将层切换视为顺序进行的操作——不要同时持有来自两个层的活动查询构造器。
- 当目标选项未发生变化时，`ConnectionHelper::needToReconnect()` 会跳过多余的重连。

## 另请参阅

- [命令](commands.md)
- [安全](security.md)
