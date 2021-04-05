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
use \Illuminate\Database\MigrationServiceProvider as BaseMigrationServiceProvider;

/**
 * Class MigrationServiceProvider
 * @package Dskripchenko\Schemify\Providers
 */
class MigrationServiceProvider extends BaseMigrationServiceProvider
{
    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateCommand()
    {
        $this->app->singleton('command.migrate', function ($app) {
            return new MigrateCommand($app['migrator']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateFreshCommand()
    {
        $this->app->singleton('command.migrate.fresh', function () {
            return new FreshCommand;
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
            return new RefreshCommand();
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
