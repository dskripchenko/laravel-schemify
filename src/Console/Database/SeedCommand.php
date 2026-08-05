<?php

namespace Dskripchenko\Schemify\Console\Database;

use Dskripchenko\Schemify\Traits\HasLayerOption;
use Dskripchenko\Schemify\Traits\RunByLayer;
use Illuminate\Database\Console\Seeds\SeedCommand as BaseSeedCommand;
use Illuminate\Database\Eloquent\Model;

/**
 * Class SeedCommand
 */
class SeedCommand extends BaseSeedCommand
{
    use HasLayerOption, RunByLayer;

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
