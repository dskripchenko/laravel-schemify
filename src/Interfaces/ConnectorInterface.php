<?php

namespace Dskripchenko\Schemify\Interfaces;

use Illuminate\Database\ConnectionInterface;

/**
 * Interface ConnectorInterface
 */
interface ConnectorInterface
{
    public function refreshConnection(): ConnectionInterface;

    public function getPreparedConnection(ConnectionInterface $connection, $schema): ConnectionInterface;

    /**
     * @return ConnectorInterface|null Null when no layer with the given name exists.
     */
    public static function getLayerItemByName($name): ?ConnectorInterface;

    /**
     * @param  null  $type
     */
    public static function getAllLayerItems($type = null): iterable;
}
