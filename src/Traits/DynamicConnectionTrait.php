<?php

namespace Dskripchenko\Schemify\Traits;

use Dskripchenko\Schemify\Facades\LayerItemConnector;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;

/**
 * Class DynamicConnectionTrait
 * @package Dskripchenko\Schemify\Traits
 */
trait DynamicConnectionTrait
{
    /**
     * @return Connection|ConnectionInterface
     * @throws \Exception
     */
    public function getConnection()
    {
        $layerItem = LayerItemConnector::getLayerItemByName($this->getLayerItemName());
        if(!$layerItem) {
            throw new \Exception("Not found 'LayerItem' with name - {$this->getLayerItemName()}");
        }
        return $layerItem->refreshConnection();
    }

    public function getLayerItemName():string
    {
        return 'main';
    }
}
