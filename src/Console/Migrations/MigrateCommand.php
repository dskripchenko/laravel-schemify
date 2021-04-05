<?php

namespace Dskripchenko\Schemify\Console\Migrations;

use Dskripchenko\Schemify\Traits\PathByLayer;
use Dskripchenko\Schemify\Traits\RunByLayer;
use \Illuminate\Database\Console\Migrations\MigrateCommand as BaseMigrateCommand;

/**
 * Class MigrateCommand
 * @package Dskripchenko\Schemify\Console\Migrations
 */
class MigrateCommand extends BaseMigrateCommand
{
    use PathByLayer, RunByLayer;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate {--database= : The database connection to use}
                {--force : Force the operation to run when in production}
                {--path=* : The path(s) to the migrations files to be executed}
                {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                {--pretend : Dump the SQL queries that would be run}
                {--seed : Indicates if the seed task should be re-run}
                {--step : Force the migrations to be run so they can be rolled back individually}
                {--layer=main : Слой к которому применяется команда.}';

    /**
     * @throws \Exception
     */
    public function handle()
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        $this->prepareDatabase();

        $this->runByLayer(
            function (&$instance, $database) {
                $originConnection = config('database.default');
                $instance->migrator->setConnection($database);
                $instance->migrator->setOutput($instance->output)
                    ->run(
                        $instance->getMigrationPaths(),
                        [
                            'pretend' => $instance->option('pretend'),
                            'step'    => $instance->option('step'),
                        ]
                    );

                if ($instance->option('seed')
                    && !$instance->option(
                        'pretend'
                    )
                ) {
                    $instance->call(
                        'db:seed',
                        [
                            '--force' => true,
                            '--layer' => $instance->option('layer')
                        ]
                    );
                }
                $instance->migrator->setConnection($originConnection);
            }
        );
    }


    protected function prepareDatabase()
    {
        $this->call(
            'migrate:install',
            array_filter(
                [
                    '--database' => $this->option('database'),
                    '--layer'   => $this->input->getOption('layer'),
                ]
            )
        );
    }
}
