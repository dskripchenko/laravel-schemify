<?php

namespace Dskripchenko\Schemify\Facades;

use Dskripchenko\Schemify\Interfaces\ConnectorInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ConnectorInterface|null getLayerItemByName($name)
 * @method static iterable getAllLayerItems($type = null)
 *
 * Class LayerItemConnector
 */
class LayerItemConnector extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'layer_item_connector';
    }
}
