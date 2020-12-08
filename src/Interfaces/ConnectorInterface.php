<?php

namespace Dskripchenko\Schemify\Interfaces;

interface ConnectorInterface
{
    /**
     * @param string $connectionName
     */
    public function refreshConnection(string $connectionName): void;

    /**
     * @param $id
     * @return ConnectorInterface
     */
    public static function getConnectorById($id): ConnectorInterface;

    /**
     * @return iterable
     */
    public static function getAllConnectors(): iterable;
}
