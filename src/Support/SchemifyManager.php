<?php

namespace Dskripchenko\Schemify\Support;

use Closure;
use Dskripchenko\Schemify\Models\LayerItem;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Runtime entry point for layer switching.
 *
 * Keeps track of the currently active layer so application code (and the
 * DynamicConnectionTrait) can resolve the right schema without re-reading the
 * --layer console option. Switching reconfigures the shared layer connection
 * once; ConnectionHelper::needToReconnect() avoids redundant reconnects.
 */
class SchemifyManager
{
    protected ?string $current = null;

    /**
     * Name of the currently active layer, or null when none is active
     * (i.e. the app is on its default/central connection).
     */
    public function current(): ?string
    {
        return $this->current;
    }

    /**
     * The database connection name Schemify reconfigures per layer.
     */
    public function connectionName(): string
    {
        return config('schemify.connection', config('database.layer'));
    }

    /**
     * Make the given layer the active one and return its live connection.
     *
     * @throws InvalidArgumentException when the layer does not exist.
     */
    public function switchTo(string $name): ConnectionInterface
    {
        $layerItem = LayerItem::getLayerItemByName($name);

        if (! $layerItem) {
            throw new InvalidArgumentException("Layer '{$name}' not found.");
        }

        $connection = $layerItem->refreshConnection();
        $this->current = $name;

        return $connection;
    }

    /**
     * Run a callback with the given layer active, then restore the previous
     * layer (or the default connection) afterwards — even if it throws.
     *
     * @return mixed the callback's return value
     */
    public function use(string $name, Closure $callback)
    {
        $previous = $this->current;

        try {
            $this->switchTo($name);

            return $callback();
        } finally {
            if ($previous !== null) {
                $this->switchTo($previous);
            } else {
                $this->forget();
            }
        }
    }

    /**
     * Drop the active layer and purge the layer connection so the next
     * resolution rebuilds from the base template.
     */
    public function forget(): void
    {
        $this->current = null;
        DB::purge($this->connectionName());
    }
}
