<?php

namespace Dskripchenko\Schemify\Console\Migrations;


use Dskripchenko\Schemify\Console\Components\PathByTarget;
use Dskripchenko\Schemify\Console\Components\RunByTarget;

class MigrateCommand extends
    \Illuminate\Database\Console\Migrations\MigrateCommand
{
    use PathByTarget, RunByTarget;

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
                {--target=main : The purpose of the command. Available values are main schema, client schemas. (main|schemify[:<id>])}';

    /**
     * @throws \Exception
     */
    public function handle()
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        $this->prepareDatabase();

        $this->runByTarget(
            function (&$instance, $database) {
                $originConnection = config('database.default');
                $instance->migrator->setConnection($database);
                $instance->migrator->setOutput($instance->output)
                    ->run(
                        $instance->getMigrationPaths(),
                        [
                            'pretend' => $instance->option('pretend'),
                            'step' => $instance->option('step'),
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
                            '--target' => $instance->option(
                                'target'
                            )
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
                    '--target' => $this->input->getOption('target'),
                ]
            )
        );
    }
}
