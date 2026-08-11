<?php

namespace Dskripchenko\Schemify\Console\Migrations;

use Dskripchenko\Schemify\Traits\HasLayerOption;
use Dskripchenko\Schemify\Traits\PathByLayer;
use Dskripchenko\Schemify\Traits\RunByLayer;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Console\Migrations\InstallCommand as BaseInstallCommand;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Support\Facades\DB;

/**
 * Class InstallCommand
 */
class InstallCommand extends BaseInstallCommand
{
    use HasLayerOption, PathByLayer, RunByLayer;

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $this->runByLayer(function (&$instance, $database) {
            $connection = DB::connection($database);
            $resolver = new ConnectionResolver([$database => $connection]);
            // Laravel 11+: database.migrations is an array {table, ...}; a string before that.
            $migrations = config('database.migrations');
            $table = is_array($migrations) ? ($migrations['table'] ?? 'migrations') : (string) $migrations;
            $repository = new DatabaseMigrationRepository($resolver, $table);
            $repository->setSource($database);
            if (! $repository->repositoryExists()) {
                $repository->createRepository();
                $instance->info('Migration table created successfully.');
            }
        });
    }
}
