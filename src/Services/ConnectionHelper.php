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

        if (! static::needToReconnect($options)) {
            return DB::connection($connectionName);
        }

        $schema = Arr::get($options, 'schema');

        // Самый частый случай — сменилась только схема на уже открытом
        // соединении: так выглядит каждый запрос клиента. Нового соединения он
        // не требует, достаточно переключить search_path.
        //
        // Раньше здесь всегда шёл purge + новое подключение, то есть новый TCP
        // и новая аутентификация. В приложении с одним слоем на запрос это
        // стоило 12% времени каждого HTTP-запроса (профилирование printable,
        // 2026-07-30), и ровно столько же — каждой задачи очереди.
        //
        // Изоляция сохраняется: search_path выставляется в одну схему, без
        // public, как и при подключении заново.
        if ($schema !== null && static::onlySchemaChanged($options) && static::isConnected($connectionName)) {
            static::prepareConnection($options);
            $connection = DB::connection($connectionName);
            $connection->unprepared('SET search_path TO '.SchemaName::quote((string) $schema).';');

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
     * Отличается ли запрошенное подключение от текущего только схемой.
     *
     * Слой может жить на другом сервере (`db_connections`), и тогда сменой
     * search_path не обойтись — нужно настоящее переподключение.
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
     * Установлено ли соединение прямо сейчас.
     *
     * Проверяем именно резолв, а не конфигурацию: на первом переключении в
     * процессе соединения ещё нет, и переключать search_path не на чем.
     */
    protected static function isConnected(string $connectionName): bool
    {
        return array_key_exists($connectionName, DB::getConnections());
    }

    /**
     * Схемы, существование которых уже подтверждено в этом процессе.
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

        // Ключ включает и сервер: слои могут жить на разных подключениях
        // (`db_connections`), и одноимённая схема там — разные схемы.
        $key = $connection instanceof Connection
            ? $connection->getName().'/'.$connection->getDatabaseName().'@'.$schema
            : '@'.$schema;

        // `CREATE SCHEMA IF NOT EXISTS` выполнялся на каждом переключении слоя,
        // то есть на каждом запросе клиента. Это DDL: он берёт блокировку и на
        // горячем пути не нужен — схема создаётся при провижининге слоя, а
        // здесь лишь подстраховка на случай, когда её не создали.
        //
        // Помним только подтверждённое существование, поэтому худшее, что
        // может случиться при промахе, — лишний безобидный CREATE.
        if (isset(static::$ensured[$key])) {
            return $connection;
        }

        // Schema name is validated + double-quoted before hitting raw SQL.
        $connection->unprepared('CREATE SCHEMA IF NOT EXISTS '.SchemaName::quote((string) $schema).';');
        static::$ensured[$key] = true;

        return $connection;
    }

    /**
     * Забыть подтверждения существования схем.
     *
     * Нужно там, где схема исчезает из-под процесса: удаление слоя, откат
     * миграций, тесты с пересозданием базы.
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
