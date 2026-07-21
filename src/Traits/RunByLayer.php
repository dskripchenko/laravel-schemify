<?php

namespace Dskripchenko\Schemify\Traits;

use Dskripchenko\Schemify\Facades\LayerItemConnector;

/**
 * Trait RunByLayer
 */
trait RunByLayer
{
    use ResolvesLayerOption;

    public function runByLayer(\Closure $callback)
    {
        $connectionName = config('database.layer');
        $layer = $this->layerOption();

        if ($this->isCentralLayer()) {
            $callback($this, $this->option('database'));

            return;
        }

        if ($layerItem = LayerItemConnector::getLayerItemByName($layer)) {
            $layerItem->refreshConnection();
            $callback($this, $connectionName);

            return;
        }

        foreach (LayerItemConnector::getAllLayerItems($layer) as $layerItem) {
            $layerItem->refreshConnection();
            $callback($this, $connectionName);
        }
    }
}
