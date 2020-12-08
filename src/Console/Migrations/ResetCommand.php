<?php

namespace Dskripchenko\Schemify\Console\Migrations;


use Dskripchenko\Schemify\Console\Components\PathByTarget;
use Dskripchenko\Schemify\Console\Components\RunByTarget;
use Symfony\Component\Console\Input\InputOption;

class ResetCommand extends \Illuminate\Database\Console\Migrations\ResetCommand
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
                if (!$instance->migrator->repositoryExists()) {
                    return $instance->comment('Migration table not found.');
                }
                $instance->migrator->setOutput($instance->output)->reset(
                    $instance->getMigrationPaths(),
                    $instance->option('pretend')
                );
                $instance->migrator->setConnection($originConnection);
            }
        );
    }
}
