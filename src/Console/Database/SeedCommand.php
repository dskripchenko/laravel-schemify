<?php


namespace Dskripchenko\Schemify\Console\Database;


use Dskripchenko\Schemify\Console\Components\RunByTarget;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Input\InputOption;

class SeedCommand extends \Illuminate\Database\Console\Seeds\SeedCommand
{
    use RunByTarget;

    protected function getOptions()
    {
        return array_merge(
            parent::getOptions(),
            [
                [
                    'target',
                    't',
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
                $instance->resolver->setDefaultConnection($database);

                Model::unguarded(
                    function () use ($instance) {
                        $instance->getSeeder()->__invoke();
                    }
                );

                $instance->info('Database seeding completed successfully.');
            }
        );
    }
}
