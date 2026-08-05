<?php

namespace Dskripchenko\Schemify\Console\Migrations;

use Dskripchenko\Schemify\Traits\HasLayerOption;
use Dskripchenko\Schemify\Traits\PathByLayer;
use Dskripchenko\Schemify\Traits\RunByLayer;
use Illuminate\Database\Console\Migrations\ResetCommand as BaseResetCommand;

/**
 * Class ResetCommand
 */
class ResetCommand extends BaseResetCommand
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
