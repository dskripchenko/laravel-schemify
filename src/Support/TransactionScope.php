<?php

namespace Dskripchenko\Schemify\Support;

use Closure;
use Dskripchenko\Schemify\Exceptions\OutsideTransactionException;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Support\Facades\DB;
use WeakMap;

/**
 * The layer's search_path, scoped to transactions.
 *
 * In the default `session` scope a switch runs `SET search_path`, which lives
 * as long as the server connection. Behind PgBouncer in transaction mode that
 * server connection goes to another client after every transaction, and the
 * path goes with it — one tenant would read another's schema.
 *
 * In the `transaction` scope a switch only records the path. It is applied with
 * `set_config('search_path', …, true)` (the SET LOCAL form) as the first
 * statement of every transaction on the connection, and dies with it. A query
 * on a connection with an active layer outside any transaction is refused: it
 * would run with whatever path the pooled server connection has.
 *
 * Console runs that need a plain session (the migrator reads its repository
 * outside transactions) go through {@see withSession()}, which also lets the
 * host point them past the pooler (`schemify.search_path.direct`).
 */
final class TransactionScope
{
    /** @var array<string, string> connection name => search_path value */
    private static array $paths = [];

    private static int $sessionDepth = 0;

    /** @var WeakMap<Connection, true>|null */
    private static ?WeakMap $guarded = null;

    public static function enabled(): bool
    {
        return config('schemify.search_path.scope', 'session') === 'transaction'
            && self::$sessionDepth === 0;
    }

    /**
     * Whether the host configured the transaction scope, regardless of a
     * session override currently in effect.
     */
    public static function configured(): bool
    {
        return config('schemify.search_path.scope', 'session') === 'transaction';
    }

    public static function inSessionOverride(): bool
    {
        return self::configured() && self::$sessionDepth > 0;
    }

    /**
     * Run a callback with the session scope, e.g. migrations of a layer. The
     * layer connection is dropped on the way out, so the session path set
     * meanwhile never reaches pooled use.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function withSession(Closure $callback): mixed
    {
        if (! self::configured()) {
            return $callback();
        }

        $name = (string) config('database.layer');
        $template = config("database.connections.{$name}");
        self::$sessionDepth++;

        try {
            return $callback();
        } finally {
            self::$sessionDepth--;
            unset(self::$paths[$name]);
            // Back to the pooled template: the session run may have pointed the
            // connection past the pooler and left a session path on it.
            config(["database.connections.{$name}" => $template]);
            DB::purge($name);
        }
    }

    /**
     * The search_path value for a schema: the schema first, then the schemas
     * every layer needs (`schemify.search_path.append`, e.g. extensions).
     */
    public static function pathFor(string $schema): string
    {
        $schemas = [$schema, ...array_values((array) config('schemify.search_path.append', []))];

        return implode(', ', array_map(
            static fn ($name): string => SchemaName::quote((string) $name),
            $schemas,
        ));
    }

    public static function activate(Connection $connection, string $schema): void
    {
        self::$paths[$connection->getName()] = self::pathFor($schema);
        self::guard($connection);

        if ($connection->transactionLevel() > 0) {
            self::apply($connection);
        }
    }

    /**
     * Drop the recorded path. Inside a transaction the default path is
     * restored for the rest of it.
     */
    public static function clear(Connection $connection): void
    {
        $had = isset(self::$paths[$connection->getName()]);
        unset(self::$paths[$connection->getName()]);

        if ($had && $connection->transactionLevel() > 0) {
            $connection->statement('SET LOCAL search_path TO DEFAULT');
        }
    }

    /**
     * Forget the path without touching the server, e.g. before a reconnect.
     */
    public static function forget(string $connectionName): void
    {
        unset(self::$paths[$connectionName]);
    }

    public static function current(string $connectionName): ?string
    {
        return self::$paths[$connectionName] ?? null;
    }

    public static function onTransactionBeginning(TransactionBeginning $event): void
    {
        $connection = $event->connection;

        if ($connection->transactionLevel() === 1 && isset(self::$paths[$connection->getName()])) {
            self::apply($connection);
        }
    }

    private static function apply(Connection $connection): void
    {
        $connection->select(
            "select set_config('search_path', ?, true)",
            [self::$paths[$connection->getName()]],
        );
    }

    private static function guard(Connection $connection): void
    {
        self::$guarded ??= new WeakMap;

        if (isset(self::$guarded[$connection])) {
            return;
        }

        self::$guarded[$connection] = true;

        $connection->beforeExecuting(static function (string $query, array $bindings, Connection $connection): void {
            if (isset(self::$paths[$connection->getName()]) && $connection->transactionLevel() === 0) {
                throw OutsideTransactionException::for($connection->getName(), $query);
            }
        });
    }
}
