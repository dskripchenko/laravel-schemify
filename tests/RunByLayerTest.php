<?php

declare(strict_types=1);

namespace Dskripchenko\Schemify\Tests;

use Dskripchenko\Schemify\Traits\RunByLayer;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;

/**
 * The contract of RunByLayer: the callback receives a connection NAME.
 *
 * Every caller in this package uses the argument as a name —
 * `DB::connection($database)`, `$resolver->setDefaultConnection($database)`,
 * `$migrator->setConnection($database)`. None of them accepts "null means the
 * default"; the first one to be handed null failed with a bare "Undefined
 * array key driver", pointing at Laravel's DatabaseManager rather than at the
 * missing `--database` option that actually caused it.
 *
 * The regression was reachable from the plainest possible invocation —
 * `php artisan db:seed --force` with no layer and no database — which is the
 * vanilla Laravel command this package promises to keep working.
 */
class RunByLayerTest extends TestCase
{
    public function test_central_run_without_database_option_passes_the_default_connection(): void
    {
        $received = $this->runCommandCapturingConnection([]);

        $this->assertSame(config('database.default'), $received);
        $this->assertNotNull($received, 'null is not a connection name');
    }

    public function test_explicit_database_option_still_wins(): void
    {
        // The fallback must not override a deliberate choice.
        config(['database.connections.other' => config('database.connections.'.config('database.default'))]);

        $received = $this->runCommandCapturingConnection(['--database' => 'other']);

        $this->assertSame('other', $received);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function runCommandCapturingConnection(array $input): mixed
    {
        $command = new class extends Command
        {
            use RunByLayer;

            protected $name = 'schemify:test-run-by-layer';

            /** @var mixed */
            public $received = 'not called';

            protected function configure(): void
            {
                parent::configure();

                $this->getDefinition()->addOption(
                    new InputOption('database', null, InputOption::VALUE_OPTIONAL)
                );
                $this->getDefinition()->addOption(
                    new InputOption('layer', null, InputOption::VALUE_OPTIONAL)
                );
            }

            public function handle(): int
            {
                $this->runByLayer(function ($instance, $database): void {
                    $instance->received = $database;
                });

                return self::SUCCESS;
            }
        };

        $command->setLaravel($this->app);
        $command->run(new ArrayInput($input, $command->getDefinition()), new NullOutput);

        return $command->received;
    }
}
