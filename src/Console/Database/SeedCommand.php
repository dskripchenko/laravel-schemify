<?php

namespace Dskripchenko\Schemify\Console\Database;

use Dskripchenko\Schemify\Traits\RunByLayer;
use Illuminate\Database\Console\Seeds\SeedCommand as BaseSeedCommand;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class SeedCommand
 */
class SeedCommand extends BaseSeedCommand
{
    use RunByLayer;

    protected function getOptions()
    {
        return array_merge(parent::getOptions(), [
            ['layer', null, InputOption::VALUE_OPTIONAL, 'Слой к которому применяется команда.', null],
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

        $this->runByLayer(function (&$instance, $database) {
            $instance->resolver->setDefaultConnection($database);

            Model::unguarded(function () use ($instance) {
                $instance->getSeeder()->__invoke();
            });

            $instance->info('Database seeding completed successfully.');
        });
    }
}
