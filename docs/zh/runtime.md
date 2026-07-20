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

## 注意事项

- `switchTo()` 会重新配置单个共享连接（`schemify.connection`）。在同一个请求内，
  应将层切换视为顺序进行的操作——不要同时持有来自两个层的活动查询构造器。
- 当目标选项未发生变化时，`ConnectionHelper::needToReconnect()` 会跳过多余的重连。

## 另请参阅

- [命令](commands.md)
- [安全](security.md)
