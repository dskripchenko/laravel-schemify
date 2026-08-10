<?php

namespace Dskripchenko\Schemify\Traits;

use Dskripchenko\Schemify\Facades\LayerItemConnector;

/**
 * Trait RunByLayer
 *
 * Runs a console command against one layer, every layer, or the central one.
 *
 * The callback always receives a connection NAME, never null. Callers use it
 * as a name — `DB::connection($database)`, `setDefaultConnection($database)`,
 * `migrator->setConnection($database)` — so "null means the default" is not a
 * contract this trait may hand out.
 */
trait RunByLayer
{
    use ResolvesLayerOption;

    public function runByLayer(\Closure $callback)
    {
        $connectionName = config('database.layer');
        $layer = $this->layerOption();

        if ($this->isCentralLayer()) {
            // `--database` is optional, and a plain `php artisan db:seed` is
            // the vanilla Laravel invocation — it must work. Passing the raw
            // option through meant passing null, and the first caller to treat
            // it as a name blew up with "Undefined array key driver" far from
            // here. Resolve the default once, at the boundary.
            $callback($this, $this->option('database') ?: config('database.default'));

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
