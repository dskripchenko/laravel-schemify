<?php


namespace Dskripchenko\Schemify\Console\Database;


use Dskripchenko\Schemify\Console\Components\RunByTarget;
use Symfony\Component\Console\Input\InputOption;

class WipeCommand extends \Illuminate\Database\Console\WipeCommand
{
    use RunByTarget;

    protected function getOptions()
    {
        return array_merge(
            parent::getOptions(),
            [
                [
                    'target',
                    't',
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
        if (!$this->confirmToProceed()) {
            return;
        }

        $this->runByTarget(
            function (&$instance, $database) {
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
            }
        );
    }
}
