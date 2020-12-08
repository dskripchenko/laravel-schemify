<?php

namespace Dskripchenko\Schemify\Console\Migrations;


use Dskripchenko\Schemify\Console\Components\PathByTarget;
use Dskripchenko\Schemify\Console\Components\RunByTarget;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;

class InstallCommand extends \Illuminate\Database\Console\Migrations\InstallCommand
{
    use PathByTarget, RunByTarget;

    protected function getOptions()
    {
        return array_merge(
            parent::getOptions(),
            [
                [
                    'target',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'The purpose of the command. Available values are main schema, client schemas. (main|schemify[:<id>])',
                    'main'
                ],
            ]
        );
    }


    /**
     * @throws \Exception
     */
    public function handle()
    {
        $this->runByTarget(
            function (&$instance, $database) {
                $connection = DB::connection($database);
                $resolver = new ConnectionResolver([$database => $connection]);
                $repository = new DatabaseMigrationRepository($resolver, config('database.migrations'));
                $repository->setSource($database);
                if (!$repository->repositoryExists()) {
                    $repository->createRepository();
                    $instance->info('Migration table created successfully.');
                }
            }
        );
    }
}
