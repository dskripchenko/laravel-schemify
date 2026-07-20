---
title: Security
locale: en
status: stable
---

# Security

## Encrypted credentials

`db_connections.password` is stored **encrypted** via Laravel's `encrypted`
cast. The column is `text` (it holds ciphertext), and reads transparently
decrypt:

```php
$connection->password = 'secret';   // encrypted on save
$connection->password;              // 'secret' on read
```

This requires `APP_KEY` to be set. If you rotate `APP_KEY`, re-encrypt existing
rows the same way Laravel does for any encrypted attribute.

### Migrating plaintext credentials (from 2.x)

2.x stored the password in plaintext. After upgrading, re-save each connection
so the value is encrypted:

```php
use Dskripchenko\Schemify\Models\DbConnection;

DbConnection::withTrashed()->get()->each(function ($c) {
    $c->password = /* current plaintext password */;
    $c->save();
});
```

## Schema-name safety

Schema names reach raw SQL in `CREATE SCHEMA` / `DROP SCHEMA`, so they are never
interpolated unchecked. `Dskripchenko\Schemify\Support\SchemaName`:

- **validates** against a conservative identifier shape — must start with a
  letter or underscore, then letters / digits / underscores, max 63 bytes;
- **double-quotes** the identifier for the statement.

```php
use Dskripchenko\Schemify\Support\SchemaName;

SchemaName::isValid('acme');                 // true
SchemaName::isValid('a"; DROP SCHEMA x');    // false
SchemaName::quote('acme');                   // "acme"
SchemaName::assertValid('bad name');         // throws InvalidArgumentException
```

`layers:new` rejects an invalid `--schema` before touching the database, and the
connection layer quotes the schema on every `CREATE SCHEMA IF NOT EXISTS`.

## Credentials in `layers:new`

By default `layers:new` clones the app's **default** database connection
config into a new `db_connections` row (password encrypted). Pass
`--connection=<id>` to reuse an existing connection instead of creating one.

## See also

- [Runtime layer switching](runtime.md)
- [Getting started](getting-started.md)
