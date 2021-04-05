<?php

namespace Dskripchenko\Schemify\Console\Migrations;

use Dskripchenko\Schemify\Traits\PathByLayer;
use Dskripchenko\Schemify\Traits\RunByLayer;
use Symfony\Component\Console\Input\InputOption;
use \Illuminate\Database\Console\Migrations\FreshCommand as BaseFreshCommand;

/**
 * Class FreshCommand
 * @package Dskripchenko\Schemify\Console\Migrations
 */
class FreshCommand extends BaseFreshCommand
{
    use PathByLayer, RunByLayer;


    protected function getOptions(){
        return array_merge(parent::getOptions(),[
            ['layer', null, InputOption::VALUE_OPTIONAL, 'Слой к которому применяется команда', 'main'],
        ]);
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $database = $this->input->getOption('database');

        $this->call('db:wipe', array_filter([
            '--database' => $database,
            '--drop-views' => $this->option('drop-views'),
            '--drop-types' => $this->option('drop-types'),
            '--force' => true,
            '--layer' => $this->option('layer'),
        ]));

        $this->call('migrate', array_filter([
            '--database' => $database,
            '--path' => $this->input->getOption('path'),
            '--realpath' => $this->input->getOption('realpath'),
            '--force' => true,
            '--step' => $this->option('step'),
            '--layer' => $this->option('layer'),
        ]));

        if ($this->needsSeeding()) {
            $this->runSeeder($database);
        }
    }

    /**
     * Run the database seeder command.
     *
     * @param  string  $database
     * @return void
     */
    protected function runSeeder($database)
    {
        $this->call('db:seed', array_filter([
            '--database' => $database,
            '--class' => $this->option('seeder') ?: 'DatabaseSeeder',
            '--force' => true,
            '--layer' => $this->option('layer'),
        ]));
    }
}
