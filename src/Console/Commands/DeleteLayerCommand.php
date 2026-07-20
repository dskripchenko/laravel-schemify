<?php

namespace Dskripchenko\Schemify\Console\Commands;

use Dskripchenko\Schemify\Models\LayerItem;
use Dskripchenko\Schemify\Support\SchemaName;
use Illuminate\Console\Command;

/**
 * `layers:delete` — remove a layer from the registry. With --drop-schema it
 * also DROPs the PostgreSQL schema (destructive, CASCADE).
 */
class DeleteLayerCommand extends Command
{
    protected $signature = 'layers:delete
        {name : Layer name}
        {--drop-schema : Also DROP the PostgreSQL schema and everything in it (destructive)}
        {--force : Skip confirmation}';

    protected $description = 'Delete a Schemify layer (optionally dropping its schema)';

    public function handle(): int
    {
        $name = (string) $this->argument('name');

        $layer = LayerItem::query()->where('name', $name)->first();
        if (! $layer) {
            $this->error("Layer '{$name}' not found.");

            return self::FAILURE;
        }

        $dropSchema = (bool) $this->option('drop-schema');

        if (! $this->option('force')) {
            $what = $dropSchema
                ? "layer '{$name}' AND drop schema '{$layer->schema_name}' (all data lost)"
                : "layer '{$name}' (schema kept)";
            if (! $this->confirm("Delete {$what}?", false)) {
                $this->line('Aborted.');

                return self::SUCCESS;
            }
        }

        if ($dropSchema) {
            $connection = app('schemify')->switchTo($name);
            $connection->unprepared('DROP SCHEMA IF EXISTS '.SchemaName::quote($layer->schema_name).' CASCADE;');
            app('schemify')->forget();
            $this->info("Dropped schema '{$layer->schema_name}'.");
        }

        // Hard delete so the unique name is freed for reuse.
        $layer->forceDelete();

        $this->info("Layer '{$name}' deleted.");

        return self::SUCCESS;
    }
}
