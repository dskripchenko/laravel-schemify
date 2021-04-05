<?php

namespace Dskripchenko\Schemify\Interfaces;

use Illuminate\Database\ConnectionInterface;

/**
 * Interface ConnectorInterface
 * @package Dskripchenko\Schemify\Interfaces
 */
interface ConnectorInterface
{
    /**
     * @return ConnectionInterface
     */
    public function refreshConnection(): ConnectionInterface;

    /**
     * @param ConnectionInterface $connection
     * @param $schema
     * @return ConnectionInterface
     */
    public function getPreparedConnection(ConnectionInterface $connection, $schema): ConnectionInterface;

    /**
     * @param $name
     * @return ConnectorInterface
     */
    public static function getLayerItemByName($name):ConnectorInterface;

    /**
     * @param null $type
     * @return iterable
     */
    public static function getAllLayerItems($type = null):iterable;
}
