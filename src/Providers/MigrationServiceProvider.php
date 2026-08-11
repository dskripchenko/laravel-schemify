<?php

namespace Dskripchenko\Schemify\Providers;

use Dskripchenko\Schemify\Console\Migrations\FreshCommand;
use Dskripchenko\Schemify\Console\Migrations\InstallCommand;
use Dskripchenko\Schemify\Console\Migrations\MigrateCommand;
use Dskripchenko\Schemify\Console\Migrations\MigrateMakeCommand;
use Dskripchenko\Schemify\Console\Migrations\RefreshCommand;
use Dskripchenko\Schemify\Console\Migrations\ResetCommand;
use Dskripchenko\Schemify\Console\Migrations\RollbackCommand;
use Dskripchenko\Schemify\Console\Migrations\StatusCommand;
use Illuminate\Database\MigrationServiceProvider as BaseMigrationServiceProvider;

/**
 * Class MigrationServiceProvider
 */
class MigrationServiceProvider extends BaseMigrationServiceProvider
{
    /**
     * Our singletons can be overridden by the deferred core
     * MigrationServiceProvider when the console kernel resolves commands —
     * which binding "wins" depends on ordering. extend() applies to the FINAL
     * binding regardless of who registered it, and deterministically returns
     * our overridden commands.
     */
    public function register()
    {
        parent::register();

        $factories = [
            MigrateCommand::class => fn ($app) => new MigrateCommand($app['migrator'], $app['events']),
            FreshCommand::class => fn ($app) => new FreshCommand($app['migrator']),
            InstallCommand::class => fn ($app) => new InstallCommand($app['migration.repository']),
            MigrateMakeCommand::class => fn ($app) => new MigrateMakeCommand($app['migration.creator'], $app['composer']),
            RefreshCommand::class => fn () => new RefreshCommand,
            ResetCommand::class => fn ($app) => new ResetCommand($app['migrator']),
            RollbackCommand::class => fn ($app) => new RollbackCommand($app['migrator']),
            StatusCommand::class => fn ($app) => new StatusCommand($app['migrator']),
        ];

        // The modern console kernel resolves commands by the class abstracts
        // Illuminate\…\*Command; the legacy command.* strings remain for BC.
        $abstracts = [
            MigrateCommand::class => ['command.migrate', \Illuminate\Database\Console\Migrations\MigrateCommand::class],
            FreshCommand::class => ['command.migrate.fresh', \Illuminate\Database\Console\Migrations\FreshCommand::class],
            InstallCommand::class => ['command.migrate.install', \Illuminate\Database\Console\Migrations\InstallCommand::class],
            MigrateMakeCommand::class => ['command.migrate.make', \Illuminate\Database\Console\Migrations\MigrateMakeCommand::class],
            RefreshCommand::class => ['command.migrate.refresh', \Illuminate\Database\Console\Migrations\RefreshCommand::class],
            ResetCommand::class => ['command.migrate.reset', \Illuminate\Database\Console\Migrations\ResetCommand::class],
            RollbackCommand::class => ['command.migrate.rollback', \Illuminate\Database\Console\Migrations\RollbackCommand::class],
            StatusCommand::class => ['command.migrate.status', \Illuminate\Database\Console\Migrations\StatusCommand::class],
        ];

        foreach ($abstracts as $ours => $targets) {
            $factory = $factories[$ours];
            foreach ($targets as $abstract) {
                $this->app->extend($abstract, function ($command, $app) use ($factory) {
                    return $factory($app);
                });
            }
        }
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateCommand()
    {
        $this->app->singleton('command.migrate', function ($app) {
            // Laravel 10+ MigrateCommand ctor requires (Migrator, Dispatcher).
            return new MigrateCommand($app['migrator'], $app['events']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateFreshCommand()
    {
        $this->app->singleton('command.migrate.fresh', function ($app) {
            // Laravel 9+ FreshCommand ctor requires a Migrator instance.
            return new FreshCommand($app['migrator']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateInstallCommand()
    {
        $this->app->singleton('command.migrate.install', function ($app) {
            return new InstallCommand($app['migration.repository']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateMakeCommand()
    {
        $this->app->singleton('command.migrate.make', function ($app) {
            // Once we have the migration creator registered, we will create the command
            // and inject the creator. The creator is responsible for the actual file
            // creation of the migrations, and may be extended by these developers.
            $creator = $app['migration.creator'];

            $composer = $app['composer'];

            return new MigrateMakeCommand($creator, $composer);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateRefreshCommand()
    {
        $this->app->singleton('command.migrate.refresh', function () {
            return new RefreshCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateResetCommand()
    {
        $this->app->singleton('command.migrate.reset', function ($app) {
            return new ResetCommand($app['migrator']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateRollbackCommand()
    {
        $this->app->singleton('command.migrate.rollback', function ($app) {
            return new RollbackCommand($app['migrator']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateStatusCommand()
    {
        $this->app->singleton('command.migrate.status', function ($app) {
            return new StatusCommand($app['migrator']);
        });
    }
}
