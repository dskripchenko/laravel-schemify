<?php

namespace Dskripchenko\Schemify\Console\Database;

use Dskripchenko\Schemify\Traits\RunByLayer;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Database\Console\Seeds\SeedCommand as BaseSeedCommand;

/**
 * Class SeedCommand
 * @package Dskripchenko\Schemify\Console\Database
 */
class SeedCommand extends BaseSeedCommand
{
    use RunByLayer;

    protected function getOptions(){
        return array_merge(parent::getOptions(),[
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

        $this->runByLayer(function (&$instance, $database){
            $instance->resolver->setDefaultConnection($database);

            Model::unguarded(function () use ($instance){
                $instance->getSeeder()->__invoke();
            });

            $instance->info('Database seeding completed successfully.');
        });
    }
}
