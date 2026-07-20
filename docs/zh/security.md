---
title: 安全
locale: zh
status: stable
---

# 安全

## 加密凭据

`db_connections.password` 通过 Laravel 的 `encrypted` 类型转换以**加密**方式存储。
该列为 `text` 类型（保存密文），读取时会透明地解密：

```php
$connection->password = 'secret';   // encrypted on save
$connection->password;              // 'secret' on read
```

这要求已设置 `APP_KEY`。如果你轮换了 `APP_KEY`，请按 Laravel 处理任何加密属性的
相同方式重新加密现有记录。

### 迁移明文凭据（来自 2.x）

2.x 以明文形式存储密码。升级后，请重新保存每条连接记录，以便对该值进行加密：

```php
use Dskripchenko\Schemify\Models\DbConnection;

DbConnection::withTrashed()->get()->each(function ($c) {
    $c->password = /* current plaintext password */;
    $c->save();
});
```

## 模式名安全

模式名会进入 `CREATE SCHEMA` / `DROP SCHEMA` 中的原始 SQL，因此绝不会未经检查地
被插值。`Dskripchenko\Schemify\Support\SchemaName`：

- 依据一个保守的标识符形态进行**校验**——必须以字母或下划线开头，随后为
  字母 / 数字 / 下划线，最长 63 字节；
- 为语句给标识符加上**双引号**。

```php
use Dskripchenko\Schemify\Support\SchemaName;

SchemaName::isValid('acme');                 // true
SchemaName::isValid('a"; DROP SCHEMA x');    // false
SchemaName::quote('acme');                   // "acme"
SchemaName::assertValid('bad name');         // throws InvalidArgumentException
```

`layers:new` 会在触及数据库之前拒绝无效的 `--schema`，并且连接层会在每一次
`CREATE SCHEMA IF NOT EXISTS` 时给模式加引号。

## `layers:new` 中的凭据

默认情况下，`layers:new` 会将应用的**默认**数据库连接配置克隆到一条新的
`db_connections` 记录中（密码经过加密）。传入 `--connection=<id>` 可复用现有连接，
而不是创建新连接。

## 另请参阅

- [运行时层切换](runtime.md)
- [快速开始](getting-started.md)
