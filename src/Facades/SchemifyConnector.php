<?php

namespace Dskripchenko\Schemify\Facades;


use Dskripchenko\Schemify\Interfaces\ConnectorInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ConnectorInterface getConnectorById($id)
 * @method static ConnectorInterface[] getAllConnectors()
 *
 * Class SchemifyConnector
 * @package Dskripchenko\Schemify\Facades
 */
class SchemifyConnector extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'schemify_connector';
    }
}
