<?php

namespace Dskripchenko\Schemify\Console\Commands;

use Dskripchenko\Schemify\Models\DbConnection;
use Dskripchenko\Schemify\Models\LayerItem;
use Dskripchenko\Schemify\Support\SchemaName;
use Illuminate\Console\Command;

/**
 * `layers:new` — register a new layer (name → schema on a connection) and
 * create its PostgreSQL schema. Optionally runs tenant migrations for it.
 */
class NewLayerCommand extends Command
{
    protected $signature = 'layers:new
        {name : Unique layer name}
        {--schema= : Schema name (defaults to the layer name)}
        {--layer= : Layer group/type (defaults to the layer name)}
        {--connection= : Reuse an existing db_connections id instead of cloning the default}
        {--migrate : Run tenant migrations against the new layer after creation}
        {--force : Skip confirmations}';

    protected $description = 'Create a new Schemify layer and its schema';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $schema = (string) ($this->option('schema') ?: $name);
        $group = (string) ($this->option('layer') ?: $name);

        if (! SchemaName::isValid($schema)) {
            $this->error("Invalid schema name '{$schema}'. Allowed: letters, digits, underscore; must start with a letter/underscore; max 63 chars.");

            return self::FAILURE;
        }

        if (LayerItem::query()->where('name', $name)->exists()) {
            $this->error("Layer '{$name}' already exists.");

            return self::FAILURE;
        }

        $connection = $this->resolveConnection();
        if (! $connection) {
            return self::FAILURE;
        }

        LayerItem::create([
            'layer' => $group,
            'name' => $name,
            'schema_name' => $schema,
            'db_connection_id' => $connection->id,
        ]);

        // Switching to the layer runs CREATE SCHEMA IF NOT EXISTS.
        app('schemify')->switchTo($name);
        app('schemify')->forget();

        $this->info("Layer '{$name}' created (schema '{$schema}' on connection #{$connection->id}).");

        if ($this->option('migrate')) {
            $this->call('migrate', ['--layer' => $name, '--force' => true]);
        }

        return self::SUCCESS;
    }

    protected function resolveConnection(): ?DbConnection
    {
        if ($id = $this->option('connection')) {
            $connection = DbConnection::query()->find($id);
            if (! $connection) {
                $this->error("db_connection #{$id} not found.");

                return null;
            }

            return $connection;
        }

        // Clone the app's default DB config into a new connection record.
        $default = config('database.default');
        $cfg = config("database.connections.{$default}", []);

        return DbConnection::create([
            'driver' => $cfg['driver'] ?? 'pgsql',
            'host' => $cfg['host'] ?? '127.0.0.1',
            'port' => (string) ($cfg['port'] ?? 5432),
            'database' => $cfg['database'] ?? '',
            'username' => $cfg['username'] ?? '',
            'password' => $cfg['password'] ?? '',
        ]);
    }
}
