<?php

namespace Dskripchenko\Schemify\Console\Commands;

use Dskripchenko\Schemify\Models\LayerItem;
use Illuminate\Console\Command;

/**
 * `layers:list` — show registered layers and where each one points.
 */
class ListLayersCommand extends Command
{
    protected $signature = 'layers:list';

    protected $description = 'List registered Schemify layers';

    public function handle(): int
    {
        $layers = LayerItem::query()->with('dbConnection')->get();

        if ($layers->isEmpty()) {
            $this->warn('No layers registered.');

            return self::SUCCESS;
        }

        $rows = $layers->map(function (LayerItem $layer) {
            $connection = $layer->dbConnection;

            return [
                $layer->id,
                $layer->layer,
                $layer->name,
                $layer->schema_name,
                $connection ? '#'.$connection->id : '—',
                $connection ? $connection->host.':'.$connection->port.'/'.$connection->database : '—',
            ];
        })->all();

        $this->table(['ID', 'Group', 'Name', 'Schema', 'Conn', 'Target'], $rows);

        return self::SUCCESS;
    }
}
