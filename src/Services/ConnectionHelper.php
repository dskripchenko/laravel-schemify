<?php

namespace Dskripchenko\Schemify\Services;

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
    public static function prepareConnection($options = [])
    {
        $connectionName = config('database.layer');
        $connection = config("database.connections.{$connectionName}", []);
        $newConnection = array_merge_deep($connection, $options);

        // Laravel-коннектор pgsql предпочитает `search_path` ключу `schema`:
        // если шаблон подключения задаёт search_path, merge одной только
        // `schema` молча НЕ переключил бы схему (isolation breach). Зеркалим.
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
        if (static::needToReconnect($options)) {
            static::prepareConnection($options);
            DB::purge($connectionName);
            $connection = DB::connection($connectionName);
            $schema = Arr::get($options, 'schema');
            if ($connector) {
                return $connector->getPreparedConnection($connection, $schema);
            }

            return static::getPreparedConnection($connection, $schema);
        }

        return DB::connection($connectionName);
    }

    /**
     * @return ConnectionInterface
     */
    public static function getPreparedConnection(ConnectionInterface $connection, $schema)
    {
        if ($schema !== null && $schema !== '') {
            // Schema name is validated + double-quoted before hitting raw SQL.
            $connection->unprepared('CREATE SCHEMA IF NOT EXISTS '.SchemaName::quote((string) $schema).';');
        }

        return $connection;
    }
}
