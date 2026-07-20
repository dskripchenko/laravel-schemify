<?php

namespace Dskripchenko\Schemify\Traits;

use Dskripchenko\Schemify\Facades\LayerItemConnector;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;

/**
 * Class DynamicConnectionTrait
 *
 * Binds an Eloquent model to a Schemify layer. If a layer is active at runtime
 * (Schemify::use()/switchTo()), the model follows it; otherwise it falls back
 * to getLayerItemName().
 */
trait DynamicConnectionTrait
{
    /**
     * @return Connection|ConnectionInterface
     *
     * @throws \Exception
     */
    public function getConnection()
    {
        $name = app('schemify')->current() ?? $this->getLayerItemName();

        $layerItem = LayerItemConnector::getLayerItemByName($name);
        if (! $layerItem) {
            throw new \Exception("Not found 'LayerItem' with name - {$name}");
        }

        return $layerItem->refreshConnection();
    }

    public function getLayerItemName(): string
    {
        return 'main';
    }
}
