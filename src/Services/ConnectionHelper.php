<?php

namespace Dskripchenko\Schemify\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ConnectionHelper
{
    /**
     * @param $connectionName
     * @param array $options
     */
    public static function prepareConnection($connectionName, $options = [])
    {
        $connection = config("database.connections.{$connectionName}", []);
        $newConnection = array_merge_deep($connection, $options);
        config(["database.connections.{$connectionName}" => $newConnection]);
    }

    /**
     * @param $connectionName
     * @param array $options
     * @return Connection
     */
    public static function reconnect($connectionName, $options = [])
    {
        DB::purge($connectionName);
        static::prepareConnection($connectionName, $options);
        $connection = DB::connection($connectionName);
        $schema = Arr::get($options, 'schema');
        return static::getPreparedConnection($connection, $schema);
    }

    /**
     * @param Connection $connection
     * @param $schema
     * @return Connection
     */
    protected static function getPreparedConnection(Connection $connection, $schema)
    {
        $connection->unprepared("CREATE SCHEMA IF NOT EXISTS {$schema};");
        return $connection;
    }
}
