<?php

namespace Dskripchenko\Schemify\Console\Migrations;


use Dskripchenko\Schemify\Console\Components\PathByTarget;
use Dskripchenko\Schemify\Console\Components\RunByTarget;
use Symfony\Component\Console\Input\InputOption;

class RefreshCommand extends \Illuminate\Database\Console\Migrations\RefreshCommand
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
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        // Next we'll gather some of the options so that we can have the right options
        // to pass to the commands. This includes options such as which database to
        // use and the path to use for the migration. Then we'll run the command.
        $database = $this->input->getOption('database');

        $path = $this->input->getOption('path');

        // If the "step" option is specified it means we only want to rollback a small
        // number of migrations before migrating again. For example, the user might
        // only rollback and remigrate the latest four migrations instead of all.
        $step = $this->input->getOption('step') ?: 0;

        if ($step > 0) {
            $this->runRollback($database, $path, $step);
        } else {
            $this->runReset($database, $path);
        }

        // The refresh command is essentially just a brief aggregate of a few other of
        // the migration commands and just provides a convenient wrapper to execute
        // them in succession. We'll also see if we need to re-seed the database.
        $this->call(
            'migrate',
            array_filter(
                [
                    '--database' => $database,
                    '--path' => $path,
                    '--realpath' => $this->input->getOption('realpath'),
                    '--force' => true,
                    '--target' => $this->option('target'),
                ]
            )
        );

        if ($this->needsSeeding()) {
            $this->runSeeder($database);
        }
    }

    /**
     * Run the rollback command.
     *
     * @param string $database
     * @param string $path
     * @param int $step
     * @return void
     */
    protected function runRollback($database, $path, $step)
    {
        $this->call(
            'migrate:rollback',
            array_filter(
                [
                    '--database' => $database,
                    '--path' => $path,
                    '--realpath' => $this->input->getOption('realpath'),
                    '--step' => $step,
                    '--force' => true,
                    '--target' => $this->option('target'),
                ]
            )
        );
    }

    /**
     * Run the reset command.
     *
     * @param string $database
     * @param string $path
     * @return void
     */
    protected function runReset($database, $path)
    {
        $this->call(
            'migrate:reset',
            array_filter(
                [
                    '--database' => $database,
                    '--path' => $path,
                    '--realpath' => $this->input->getOption('realpath'),
                    '--force' => true,
                    '--target' => $this->option('target'),
                ]
            )
        );
    }

    /**
     * Determine if the developer has requested database seeding.
     *
     * @return bool
     */
    protected function needsSeeding()
    {
        return $this->option('seed') || $this->option('seeder');
    }

    /**
     * Run the database seeder command.
     *
     * @param string $database
     * @return void
     */
    protected function runSeeder($database)
    {
        $this->call(
            'db:seed',
            array_filter(
                [
                    '--database' => $database,
                    '--class' => $this->option('seeder') ?: 'DatabaseSeeder',
                    '--force' => true,
                    '--target' => $this->option('target'),
                ]
            )
        );
    }
}
