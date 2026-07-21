<?php

namespace Dskripchenko\Schemify\Console\Migrations;

use Dskripchenko\Schemify\Traits\PathByLayer;
use Dskripchenko\Schemify\Traits\RunByLayer;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Console\Migrations\InstallCommand as BaseInstallCommand;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class InstallCommand
 */
class InstallCommand extends BaseInstallCommand
{
    use PathByLayer, RunByLayer;

    protected function getOptions()
    {
        return array_merge(parent::getOptions(), [
            ['layer', null, InputOption::VALUE_OPTIONAL, 'Слой к которому применяется команда.', null],
        ]);
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $this->runByLayer(function (&$instance, $database) {
            $connection = DB::connection($database);
            $resolver = new ConnectionResolver([$database => $connection]);
            // Laravel 11+: database.migrations — массив {table, ...}; ранее — строка.
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
