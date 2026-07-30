<?php

namespace Dskripchenko\Schemify\Support;

use Closure;
use Dskripchenko\Schemify\Events\LayerForgotten;
use Dskripchenko\Schemify\Events\LayerSwitched;
use Dskripchenko\Schemify\Models\DbConnection;
use Dskripchenko\Schemify\Models\LayerItem;
use Dskripchenko\Schemify\Services\ConnectionHelper;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Runtime entry point for layer switching.
 *
 * Keeps track of the currently active layer so application code (and the
 * DynamicConnectionTrait) can resolve the right schema without re-reading the
 * --layer console option. Switching reconfigures the shared layer connection
 * once; ConnectionHelper::needToReconnect() avoids redundant reconnects.
 *
 * Emits {@see LayerSwitched} / {@see LayerForgotten} so applications can
 * re-scope layer-dependent infrastructure (cache prefixes, storage paths).
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

        $previous = $this->current;
        $connection = $layerItem->refreshConnection();
        $this->current = $name;

        event(new LayerSwitched($previous, $name));

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
        $previous = $this->current;
        $this->current = null;
        DB::purge($this->connectionName());

        event(new LayerForgotten($previous));
    }

    /**
     * Register a new layer and create its PostgreSQL schema. Programmatic
     * equivalent of `layers:new` — usable from HTTP controllers / services.
     *
     * The previously active layer (if any) is preserved.
     *
     * @param  string  $name  unique layer name
     * @param  string|null  $schema  schema name (defaults to the layer name)
     * @param  string|null  $group  layer group/type tag (defaults to the layer name)
     * @param  int|null  $connectionId  reuse an existing db_connections id instead of cloning the default connection
     * @param  bool  $migrate  run tenant migrations against the new layer right away
     *
     * @throws InvalidArgumentException on duplicate name, unknown connection id or unsafe schema name.
     */
    public function provision(
        string $name,
        ?string $schema = null,
        ?string $group = null,
        ?int $connectionId = null,
        bool $migrate = false,
    ): LayerItem {
        $schema = SchemaName::assertValid($schema ?? $name);

        if (LayerItem::query()->where('name', $name)->exists()) {
            throw new InvalidArgumentException("Layer '{$name}' already exists.");
        }

        if ($connectionId !== null) {
            $connection = DbConnection::query()->find($connectionId);
            if (! $connection) {
                throw new InvalidArgumentException("db_connection #{$connectionId} not found.");
            }
        } else {
            $connection = $this->cloneDefaultConnection();
        }

        $layer = new LayerItem;
        $layer->fill([
            'layer' => $group ?? $name,
            'name' => $name,
            'schema_name' => $schema,
            'db_connection_id' => $connection->id,
        ])->save();

        // Switching runs CREATE SCHEMA IF NOT EXISTS; use() restores whatever
        // layer was active before provisioning.
        $this->use($name, static fn () => null);

        if ($migrate) {
            Artisan::call('migrate', ['--layer' => $name, '--force' => true]);
        }

        return $layer;
    }

    /**
     * Remove a layer from the registry. With $dropSchema the PostgreSQL
     * schema (and everything in it) is dropped — destructive.
     *
     * @throws InvalidArgumentException when the layer does not exist.
     */
    public function deprovision(string $name, bool $dropSchema = false): void
    {
        $layer = LayerItem::query()->where('name', $name)->first();

        if (! $layer) {
            throw new InvalidArgumentException("Layer '{$name}' not found.");
        }

        // Never "restore" onto the layer being removed.
        if ($this->current === $name) {
            $this->forget();
        }

        if ($dropSchema) {
            $connectionName = $this->connectionName();
            $this->use($name, static function () use ($connectionName, $layer): void {
                DB::connection($connectionName)
                    ->unprepared('DROP SCHEMA IF EXISTS '.SchemaName::quote($layer->schema_name).' CASCADE;');
            });

            // Схемы больше нет — снимаем подтверждение, иначе процесс продолжит
            // считать её существующей и перестанет её создавать.
            ConnectionHelper::forgetEnsuredSchemas($layer->schema_name);
        }

        // Hard delete so the unique name is freed for reuse.
        $layer->forceDelete();
    }

    /**
     * Clone the app's default DB config into a new connection record.
     */
    protected function cloneDefaultConnection(): DbConnection
    {
        $default = config('database.default');
        $cfg = config("database.connections.{$default}", []);

        $connection = new DbConnection;
        $connection->fill([
            'driver' => $cfg['driver'] ?? 'pgsql',
            'host' => $cfg['host'] ?? '127.0.0.1',
            'port' => (string) ($cfg['port'] ?? 5432),
            'database' => $cfg['database'] ?? '',
            'username' => $cfg['username'] ?? '',
            'password' => $cfg['password'] ?? '',
        ])->save();

        return $connection;
    }
}
