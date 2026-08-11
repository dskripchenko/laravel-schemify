<?php

namespace Dskripchenko\Schemify\Services;

use Closure;
use Dskripchenko\Schemify\Interfaces\ConnectorInterface;
use Dskripchenko\Schemify\Support\SchemaName;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Class ConnectionHelper
 */
class ConnectionHelper
{
    /**
     * @param  array  $options
     */
    /**
     * The layer connection's original `search_path`, before the first switch.
     *
     * @var array<string, string|null>
     */
    protected static array $pristineSearchPath = [];

    /**
     * @param  array  $options
     */
    public static function prepareConnection($options = [])
    {
        $connectionName = config('database.layer');
        $connection = config("database.connections.{$connectionName}", []);

        // Remember where to return: the connection template defines the
        // original path, and after working with a layer the connection must
        // return to exactly that, not to some arbitrary schema.
        if (! array_key_exists($connectionName, static::$pristineSearchPath)) {
            static::$pristineSearchPath[$connectionName] = $connection['search_path'] ?? ($connection['schema'] ?? null);
        }
        $newConnection = array_merge_deep($connection, $options);

        // Laravel's pgsql connector prefers `search_path` over `schema`: if the
        // connection template sets search_path, merging `schema` alone would
        // silently NOT switch the schema — an isolation breach. So mirror both.
        if (isset($options['schema'])) {
            $newConnection['search_path'] = $options['schema'];
        }

        config(["database.connections.{$connectionName}" => $newConnection]);
    }

    /**
     * @param  array  $options
     */
    public static function needToReconnect($options = []): bool
    {
        $connectionName = config('database.layer');
        $connection = config("database.connections.{$connectionName}", []);

        if (isset($options['schema']) && ($connection['search_path'] ?? null) !== $options['schema']) {
            return true;
        }

        return ! empty(array_diff_assoc($options, $connection));
    }

    /**
     * @param  array  $options
     * @return Connection|ConnectionInterface
     */
    public static function reconnect($options = [], ?ConnectorInterface $connector = null)
    {
        $connectionName = config('database.layer');

        if (! static::needToReconnect($options)) {
            return DB::connection($connectionName);
        }

        $schema = Arr::get($options, 'schema');

        // The common case is a schema change on an already open connection —
        // that is what every client request looks like. It needs no new
        // connection, only a search_path switch.
        //
        // This used to always purge and reconnect — a new TCP connection and a
        // fresh authentication. In an application with one layer per request
        // that cost 12% of every HTTP request (printable profiling, 2026-07-30),
        // and the same again for every queue job.
        //
        // Isolation is preserved: search_path is set to a single schema, with
        // no public, exactly as it would be on a fresh connection.
        if ($schema !== null && static::onlySchemaChanged($options) && static::isConnected($connectionName)) {
            static::prepareConnection($options);
            $connection = DB::connection($connectionName);
            $connection->unprepared('SET search_path TO '.SchemaName::quote((string) $schema).';');
            static::syncConnectionSchema($connection, (string) $schema);

            return $connector
                ? $connector->getPreparedConnection($connection, $schema)
                : static::getPreparedConnection($connection, $schema);
        }

        static::prepareConnection($options);
        DB::purge($connectionName);
        $connection = DB::connection($connectionName);

        if ($connector) {
            return $connector->getPreparedConnection($connection, $schema);
        }

        return static::getPreparedConnection($connection, $schema);
    }

    /**
     * Whether the requested connection differs from the current one only by schema.
     *
     * A layer can live on a different server (`db_connections`), and then a
     * search_path switch is not enough — a real reconnect is required.
     *
     * @param  array  $options
     */
    protected static function onlySchemaChanged($options = []): bool
    {
        $connectionName = config('database.layer');
        $connection = config("database.connections.{$connectionName}", []);

        $diff = array_diff_assoc(
            array_filter($options, static fn ($value): bool => ! is_array($value)),
            array_filter($connection, static fn ($value): bool => ! is_array($value)),
        );
        unset($diff['schema'], $diff['search_path']);

        return $diff === [];
    }

    /**
     * Whether the connection is established right now.
     *
     * This checks resolution rather than configuration: on the first switch in
     * a process there is no connection yet, so there is nothing to switch.
     */
    protected static function isConnected(string $connectionName): bool
    {
        return array_key_exists($connectionName, DB::getConnections());
    }

    /**
     * Schemas whose existence has already been confirmed in this process.
     *
     * @var array<string, true>
     */
    protected static array $ensured = [];

    /**
     * @return ConnectionInterface
     */
    public static function getPreparedConnection(ConnectionInterface $connection, $schema)
    {
        if ($schema === null || $schema === '') {
            return $connection;
        }

        // The key includes the server: layers can live on different connections
        // (`db_connections`), where a schema of the same name is a different one.
        $key = $connection instanceof Connection
            ? $connection->getName().'/'.$connection->getDatabaseName().'@'.$schema
            : '@'.$schema;

        // `CREATE SCHEMA IF NOT EXISTS` used to run on every layer switch, that
        // is on every client request. It is DDL: it takes a lock and has no place
        // on the hot path — the schema is created when the layer is provisioned,
        // and this is only a safety net for when it was not.
        //
        // Only confirmed existence is remembered, so the worst a cache miss can
        // cost is one harmless extra CREATE.
        if (isset(static::$ensured[$key])) {
            return $connection;
        }

        // Schema name is validated + double-quoted before hitting raw SQL.
        $connection->unprepared('CREATE SCHEMA IF NOT EXISTS '.SchemaName::quote((string) $schema).';');
        static::$ensured[$key] = true;

        return $connection;
    }

    /**
     * Return the layer connection to its original schema without dropping it.
     *
     * `forget()` used to call `DB::purge()`, destroying the connection. Because
     * of that the next switch always reconnected and the saving from
     * `SET search_path` never materialised anywhere: a queue worker opened a
     * connection for every job.
     *
     * The connection is kept only if the host and database are unchanged;
     * otherwise this returns false and the caller must do a full purge.
     */
    public static function restoreDefaultSchema(): bool
    {
        $connectionName = (string) config('database.layer');

        if (! static::isConnected($connectionName)) {
            return true;   // nothing to drop
        }

        if (! array_key_exists($connectionName, static::$pristineSearchPath)) {
            return true;   // nobody switched the schema
        }

        $pristine = static::$pristineSearchPath[$connectionName];
        $connection = config("database.connections.{$connectionName}", []);

        $target = $pristine ?? 'public';
        if (! SchemaName::isValid((string) $target)) {
            return false;  // a non-standard path (a list of schemas) — safer to drop
        }

        $connection['search_path'] = $target;
        $connection['schema'] = $target;
        config(["database.connections.{$connectionName}" => $connection]);

        $live = DB::connection($connectionName);
        $live->unprepared('SET search_path TO '.SchemaName::quote((string) $target).';');
        static::syncConnectionSchema($live, (string) $target);

        return true;
    }

    /**
     * Bring a live connection's config in line with the new schema.
     *
     * While layers were switched by `purge`, the connection instance was built
     * anew each time, config included. With `SET search_path` the instance stays
     * and its config still remembers the previous schema. Queries do not show it
     * — they follow search_path — but the Postgres schema builder on Laravel
     * 11/12 takes the schema from the connection config: `Schema::hasTable()`
     * answered about the previous layer. What surfaced was `migrate:install`
     * finding the previous layer's `migrations` table, skipping creation for the
     * current one, and the `migrate` that followed failing on the missing table.
     * Laravel 13 asks `current_schema()` and was never affected.
     *
     * `Connection` has no public setter in any supported version, so the config
     * is patched through a closure bound to the instance.
     */
    protected static function syncConnectionSchema(ConnectionInterface $connection, string $schema): void
    {
        if (! $connection instanceof Connection) {
            return;
        }

        Closure::bind(function () use ($schema): void {
            $this->config['schema'] = $schema;
            $this->config['search_path'] = $schema;
        }, $connection, Connection::class)();
    }

    /**
     * Forget the confirmations that schemas exist.
     *
     * Needed wherever a schema disappears from under the process: dropping a
     * layer, rolling migrations back, tests that recreate the database.
     */
    public static function forgetEnsuredSchemas(?string $schema = null): void
    {
        if ($schema === null) {
            static::$ensured = [];

            return;
        }

        foreach (array_keys(static::$ensured) as $key) {
            if (str_ends_with($key, '@'.$schema)) {
                unset(static::$ensured[$key]);
            }
        }
    }
}
