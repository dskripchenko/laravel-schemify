<?php

namespace Dskripchenko\Schemify\Console\Migrations;

use Dskripchenko\Schemify\Traits\PathByLayer;
use Dskripchenko\Schemify\Traits\RunByLayer;
use Symfony\Component\Console\Input\InputOption;
use \Illuminate\Database\Console\Migrations\ResetCommand as BaseResetCommand;

/**
 * Class ResetCommand
 * @package Dskripchenko\Schemify\Console\Migrations
 */
class ResetCommand extends BaseResetCommand
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
        if (! $this->confirmToProceed()) {
            return;
        }

        $this->runByLayer(function (&$instance, $database){
            $originConnection = config('database.default');
            $instance->migrator->setConnection($database);
            if (! $instance->migrator->repositoryExists()) {
                return $instance->comment('Migration table not found.');
            }
            $instance->migrator->setOutput($instance->output)->reset(
                $instance->getMigrationPaths(), $instance->option('pretend')
            );
            $instance->migrator->setConnection($originConnection);
        });
    }
}
