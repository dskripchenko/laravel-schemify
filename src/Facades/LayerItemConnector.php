<?php

namespace Dskripchenko\Schemify\Facades;

use Dskripchenko\Schemify\Interfaces\ConnectorInterface;
use \Illuminate\Support\Facades\Facade;
/**
 * @method static ConnectorInterface getLayerItemByName($name)
 * @method static ConnectorInterface[] getAllLayerItems($type)()
 *
 * Class SegmentConnector
 * @package Dskripchenko\Schemify\Facades
 */
class LayerItemConnector extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'layer_item_connector';
    }
}
