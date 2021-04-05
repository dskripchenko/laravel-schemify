<?php

namespace Dskripchenko\Schemify\Traits;

use Dskripchenko\Schemify\Facades\LayerItemConnector;

/**
 * Trait RunByLayer
 * @package Dskripchenko\Schemify\Console\Components
 */
trait RunByLayer
{
    /**
     * @param \Closure $callback
     */
    public function runByLayer(\Closure $callback)
    {
        $connectionName = config('database.layer');
        $layer = $this->input->getOption('layer');

        if($layer === 'core') {
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
