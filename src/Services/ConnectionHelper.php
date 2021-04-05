<?php

namespace Dskripchenko\Schemify\Services;

use Dskripchenko\Schemify\Interfaces\ConnectorInterface;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Class ConnectionHelper
 * @package Dskripchenko\Schemify\Services
 */
class ConnectionHelper
{
    /**
     * @param array $options
     */
    public static function prepareConnection($options = [])
    {
        $connectionName = config("database.layer");
        $connection = config("database.connections.{$connectionName}", []);
        $newConnection = array_merge_deep($connection, $options);
        config(["database.connections.{$connectionName}" => $newConnection]);
    }

    /**
     * @param array $options
     * @return bool
     */
    public static function needToReconnect($options = []): bool
    {
        $connectionName = config("database.layer");
        $connection = config("database.connections.{$connectionName}", []);
        return !empty(array_diff_assoc($options, $connection));
    }

    /**
     * @param array $options
     * @param ConnectorInterface|null $connector
     * @return Connection|ConnectionInterface
     */
    public static function reconnect($options = [], ConnectorInterface $connector = null)
    {
        $connectionName = config("database.layer");
        if(static::needToReconnect($options)) {
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
     * @param ConnectionInterface $connection
     * @param $schema
     * @return ConnectionInterface
     */
    public static function getPreparedConnection(ConnectionInterface $connection, $schema)
    {
        $connection->unprepared("CREATE SCHEMA IF NOT EXISTS {$schema};");
        return $connection;
    }
}
