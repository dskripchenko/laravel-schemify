<?php

namespace Dskripchenko\Schemify\Console\Database;

use Dskripchenko\Schemify\Traits\RunByLayer;
use Illuminate\Database\Console\WipeCommand as BaseWipeCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class WipeCommand
 */
class WipeCommand extends BaseWipeCommand
{
    use RunByLayer;

    protected function getOptions()
    {
        return array_merge(parent::getOptions(), [
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
