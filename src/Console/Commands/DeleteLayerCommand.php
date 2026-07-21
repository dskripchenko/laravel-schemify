<?php

namespace Dskripchenko\Schemify\Console\Commands;

use Dskripchenko\Schemify\Models\LayerItem;
use Dskripchenko\Schemify\Support\SchemifyManager;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * `layers:delete` — remove a layer from the registry. With --drop-schema it
 * also DROPs the PostgreSQL schema (destructive, CASCADE). Thin console
 * wrapper around {@see SchemifyManager::deprovision()}.
 */
class DeleteLayerCommand extends Command
{
    protected $signature = 'layers:delete
        {name : Layer name}
        {--drop-schema : Also DROP the PostgreSQL schema and everything in it (destructive)}
        {--force : Skip confirmation}';

    protected $description = 'Delete a Schemify layer (optionally dropping its schema)';

    public function handle(SchemifyManager $schemify): int
    {
        $name = (string) $this->argument('name');
        $dropSchema = (bool) $this->option('drop-schema');

        if (! $this->option('force')) {
            $layer = LayerItem::query()->where('name', $name)->first();
            if (! $layer) {
                $this->error("Layer '{$name}' not found.");

                return self::FAILURE;
            }

            $what = $dropSchema
                ? "layer '{$name}' AND drop schema '{$layer->schema_name}' (all data lost)"
                : "layer '{$name}' (schema kept)";
            if (! $this->confirm("Delete {$what}?", false)) {
                $this->line('Aborted.');

                return self::SUCCESS;
            }
        }

        try {
            $schemify->deprovision($name, $dropSchema);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($dropSchema) {
            $this->info("Dropped schema of layer '{$name}'.");
        }
        $this->info("Layer '{$name}' deleted.");

        return self::SUCCESS;
    }
}
