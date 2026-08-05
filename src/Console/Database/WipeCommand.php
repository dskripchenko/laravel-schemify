<?php

namespace Dskripchenko\Schemify\Console\Database;

use Dskripchenko\Schemify\Traits\HasLayerOption;
use Dskripchenko\Schemify\Traits\RunByLayer;
use Illuminate\Database\Console\WipeCommand as BaseWipeCommand;

/**
 * Class WipeCommand
 */
class WipeCommand extends BaseWipeCommand
{
    use HasLayerOption, RunByLayer;

    /**
     * @throws \Exception
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $this->runByLayer(function (&$instance, $database) {
            if ($instance->option('drop-views')) {
                $instance->dropAllViews($database);

                $instance->info('Dropped all views successfully.');
            }

            $instance->dropAllTables($database);

            $instance->info('Dropped all tables successfully.');

            if ($instance->option('drop-types')) {
                $instance->dropAllTypes($database);

                $instance->info('Dropped all types successfully.');
            }
        });
    }
}
