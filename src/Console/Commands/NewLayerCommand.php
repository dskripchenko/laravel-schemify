<?php

namespace Dskripchenko\Schemify\Console\Commands;

use Dskripchenko\Schemify\Support\SchemifyManager;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * `layers:new` — register a new layer (name → schema on a connection) and
 * create its PostgreSQL schema. Thin console wrapper around
 * {@see SchemifyManager::provision()}.
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

    public function handle(SchemifyManager $schemify): int
    {
        $name = (string) $this->argument('name');
        $connectionId = $this->option('connection');

        try {
            $layer = $schemify->provision(
                name: $name,
                schema: $this->option('schema') ?: null,
                group: $this->option('layer') ?: null,
                connectionId: $connectionId !== null ? (int) $connectionId : null,
                migrate: (bool) $this->option('migrate'),
            );
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Layer '{$name}' created (schema '{$layer->schema_name}' on connection #{$layer->db_connection_id}).");

        return self::SUCCESS;
    }
}
