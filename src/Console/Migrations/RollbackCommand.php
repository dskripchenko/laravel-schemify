<?php

namespace Dskripchenko\Schemify\Console\Migrations;

use Dskripchenko\Schemify\Traits\PathByLayer;
use Dskripchenko\Schemify\Traits\RunByLayer;
use Symfony\Component\Console\Input\InputOption;
use \Illuminate\Database\Console\Migrations\RollbackCommand as BaseRollbackCommand;

/**
 * Class RollbackCommand
 * @package Dskripchenko\Schemify\Console\Migrations
 */
class RollbackCommand extends BaseRollbackCommand
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
            $instance->migrator->setOutput($instance->output)->rollback(
                $instance->getMigrationPaths(), [
                    'pretend' => $instance->option('pretend'),
                    'step' => (int) $instance->option('step'),
                ]
            );
            $instance->migrator->setConnection($originConnection);
        });
    }
}
