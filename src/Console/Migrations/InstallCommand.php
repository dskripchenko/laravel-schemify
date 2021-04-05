<?php

namespace Dskripchenko\Schemify\Console\Migrations;

use Dskripchenko\Schemify\Traits\PathByLayer;
use Dskripchenko\Schemify\Traits\RunByLayer;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;
use \Illuminate\Database\Console\Migrations\InstallCommand as BaseInstallCommand;

/**
 * Class InstallCommand
 * @package Dskripchenko\Schemify\Console\Migrations
 */
class InstallCommand extends BaseInstallCommand
{
    use PathByLayer, RunByLayer;

    protected function getOptions(){
        return array_merge(parent::getOptions(),[
            ['layer', null, InputOption::VALUE_OPTIONAL, 'Слой к которому применяется команда.', 'main'],
        ]);
    }


    /**
     * @throws \Exception
     */
    public function handle()
    {
        $this->runByLayer(function (&$instance, $database){
            $connection = DB::connection($database);
            $resolver = new ConnectionResolver([$database => $connection]);
            $repository = new DatabaseMigrationRepository($resolver, config('database.migrations'));
            $repository->setSource($database);
            if (! $repository->repositoryExists()){
                $repository->createRepository();
                $instance->info('Migration table created successfully.');
            }
        });
    }
}
