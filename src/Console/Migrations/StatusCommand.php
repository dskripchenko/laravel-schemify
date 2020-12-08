<?php

namespace Dskripchenko\Schemify\Console\Migrations;


use Dskripchenko\Schemify\Console\Components\PathByTarget;
use Dskripchenko\Schemify\Console\Components\RunByTarget;
use Symfony\Component\Console\Input\InputOption;

class StatusCommand extends \Illuminate\Database\Console\Migrations\StatusCommand
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
                $originConnection = config('database.default');
                $instance->migrator->setConnection($database);
                if (!$instance->migrator->repositoryExists()) {
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
            }
        );
    }
}

