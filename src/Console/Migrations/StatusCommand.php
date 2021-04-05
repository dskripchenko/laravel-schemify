<?php

namespace Dskripchenko\Schemify\Console\Migrations;

use Dskripchenko\Schemify\Traits\PathByLayer;
use Dskripchenko\Schemify\Traits\RunByLayer;
use Symfony\Component\Console\Input\InputOption;
use \Illuminate\Database\Console\Migrations\StatusCommand as BaseStatusCommand;

/**
 * Class StatusCommand
 * @package Dskripchenko\Schemify\Console\Migrations
 */
class StatusCommand extends BaseStatusCommand
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
            $originConnection = config('database.default');
            $instance->migrator->setConnection($database);
            if (! $instance->migrator->repositoryExists()) {
                $instance->error('Migration table not found.');
                return 1;
            }

            $ran = $instance->migrator->getRepository()->getRan();

            $batches = $instance->migrator->getRepository()->getMigrationBatches();
            $migrations = $instance->getStatusFor($ran, $batches);

            if (count($migrations) > 0) {
                $instance->table(['Ran?', 'Migration', 'Batch'], $migrations);
            } else {
                $instance->error('No migrations found');
            }
            $instance->migrator->setConnection($originConnection);
        });
    }
}

