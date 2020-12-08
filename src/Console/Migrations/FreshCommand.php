<?php

namespace Dskripchenko\Schemify\Console\Migrations;


use Dskripchenko\Schemify\Console\Components\PathByTarget;
use Dskripchenko\Schemify\Console\Components\RunByTarget;
use Symfony\Component\Console\Input\InputOption;

class FreshCommand extends \Illuminate\Database\Console\Migrations\FreshCommand
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

        $database = $this->input->getOption('database');

        $this->call(
            'db:wipe',
            array_filter(
                [
                    '--database' => $database,
                    '--drop-views' => $this->option('drop-views'),
                    '--drop-types' => $this->option('drop-types'),
                    '--force' => true,
                    '--target' => $this->option('target'),
                ]
            )
        );

        $this->call(
            'migrate',
            array_filter(
                [
                    '--database' => $database,
                    '--path' => $this->input->getOption('path'),
                    '--realpath' => $this->input->getOption('realpath'),
                    '--force' => true,
                    '--step' => $this->option('step'),
                    '--target' => $this->option('target'),
                ]
            )
        );

        if ($this->needsSeeding()) {
            $this->runSeeder($database);
        }
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
