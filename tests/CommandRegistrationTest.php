<?php

declare(strict_types=1);

namespace Dskripchenko\Schemify\Tests;

use Dskripchenko\Schemify\Console\Commands\MigrateCommand as LayersMigrateCommand;
use Dskripchenko\Schemify\Console\Database\SeedCommand;
use Dskripchenko\Schemify\Console\Database\WipeCommand;
use Dskripchenko\Schemify\Console\Migrations\FreshCommand;
use Dskripchenko\Schemify\Console\Migrations\InstallCommand;
use Dskripchenko\Schemify\Console\Migrations\MigrateCommand;
use Dskripchenko\Schemify\Console\Migrations\MigrateMakeCommand;
use Dskripchenko\Schemify\Console\Migrations\RefreshCommand;
use Dskripchenko\Schemify\Console\Migrations\ResetCommand;
use Dskripchenko\Schemify\Console\Migrations\RollbackCommand;
use Dskripchenko\Schemify\Console\Migrations\StatusCommand;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * These tests instantiate every rebound console command through the
 * container. On Laravel 11/12/13 the framework changed several command
 * constructor signatures (e.g. MigrateCommand now needs a Dispatcher,
 * FreshCommand needs a Migrator); if the package's registration bindings
 * pass the wrong arity, resolution throws ArgumentCountError here.
 */
class CommandRegistrationTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: class-string}>
     */
    public static function migrationCommandProvider(): array
    {
        return [
            'command.migrate' => ['command.migrate', MigrateCommand::class],
            'command.migrate.fresh' => ['command.migrate.fresh', FreshCommand::class],
            'command.migrate.install' => ['command.migrate.install', InstallCommand::class],
            'command.migrate.make' => ['command.migrate.make', MigrateMakeCommand::class],
            'command.migrate.refresh' => ['command.migrate.refresh', RefreshCommand::class],
            'command.migrate.reset' => ['command.migrate.reset', ResetCommand::class],
            'command.migrate.rollback' => ['command.migrate.rollback', RollbackCommand::class],
            'command.migrate.status' => ['command.migrate.status', StatusCommand::class],
            'command.seed' => ['command.seed', SeedCommand::class],
            'command.db.wipe' => ['command.db.wipe', WipeCommand::class],
        ];
    }

    #[DataProvider('migrationCommandProvider')]
    public function test_command_resolves_to_schemify_override(string $abstract, string $expected): void
    {
        $command = $this->app->make($abstract);

        $this->assertInstanceOf($expected, $command);
        $this->assertInstanceOf(Command::class, $command);
    }

    /**
     * Every per-layer command must expose the --layer option, whether it
     * comes from a $signature (MigrateCommand / MigrateMakeCommand) or from
     * the getOptions() override merged by the Symfony command definition.
     */
    #[DataProvider('migrationCommandProvider')]
    public function test_command_exposes_layer_option(string $abstract, string $expected): void
    {
        /** @var Command $command */
        $command = $this->app->make($abstract);

        $this->assertTrue(
            $command->getDefinition()->hasOption('layer'),
            "Command {$abstract} is missing the --layer option"
        );
    }

    public function test_layers_migrate_command_is_registered(): void
    {
        $names = array_keys($this->app[Kernel::class]->all());

        $this->assertContains('layers:migrate', $names);
        $this->assertContains('layers:install', $names);
    }

    public function test_layers_migrate_command_class(): void
    {
        $command = $this->app[Kernel::class]->all()['layers:migrate'];

        $this->assertInstanceOf(LayersMigrateCommand::class, $command);
    }
}
