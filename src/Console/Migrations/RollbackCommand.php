<?php

namespace Dskripchenko\Schemify\Console\Migrations;


use Dskripchenko\Schemify\Console\Components\PathByTarget;
use Dskripchenko\Schemify\Console\Components\RunByTarget;
use Symfony\Component\Console\Input\InputOption;

class RollbackCommand extends \Illuminate\Database\Console\Migrations\RollbackCommand
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
        if (!$this->confirmToProceed()) {
            return;
        }

        $this->runByTarget(
            function (&$instance, $database) {
                $originConnection = config('database.default');
                $instance->migrator->setConnection($database);
                $instance->migrator->setOutput($instance->output)->rollback(
                    $instance->getMigrationPaths(),
                    [
                        'pretend' => $instance->option('pretend'),
                        'step' => (int)$instance->option('step'),
                    ]
                );
                $instance->migrator->setConnection($originConnection);
            }
        );
    }
}
