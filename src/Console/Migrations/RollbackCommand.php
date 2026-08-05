<?php

namespace Dskripchenko\Schemify\Console\Migrations;

use Dskripchenko\Schemify\Traits\HasLayerOption;
use Dskripchenko\Schemify\Traits\PathByLayer;
use Dskripchenko\Schemify\Traits\RunByLayer;
use Illuminate\Database\Console\Migrations\RollbackCommand as BaseRollbackCommand;

/**
 * Class RollbackCommand
 */
class RollbackCommand extends BaseRollbackCommand
{
    use HasLayerOption, PathByLayer, RunByLayer;

    /**
     * @throws \Exception
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $this->runByLayer(function (&$instance, $database) {
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
