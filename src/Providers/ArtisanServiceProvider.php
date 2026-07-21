<?php

namespace Dskripchenko\Schemify\Providers;

use Dskripchenko\Schemify\Console\Database\SeedCommand;
use Dskripchenko\Schemify\Console\Database\WipeCommand;

/**
 * Class ArtisanServiceProvider
 */
class ArtisanServiceProvider extends \Illuminate\Foundation\Providers\ArtisanServiceProvider
{
    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerDbWipeCommand()
    {
        $this->app->singleton('command.db.wipe', function () {
            return new WipeCommand; // use custom App\Console\Database\WipeCommand
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerSeedCommand()
    {
        $this->app->singleton('command.seed', function ($app) {
            return new SeedCommand($app['db']);
        });
    }

    public function register()
    {
        parent::register();

        // extend() гарантирует наши override'ы поверх любого более позднего
        // (deferred) биндинга core-провайдеров; современный console kernel
        // резолвит по CLASS-абстрактам — расширяем и их, и легаси-строки.
        foreach (['command.db.wipe', \Illuminate\Database\Console\WipeCommand::class] as $abstract) {
            $this->app->extend($abstract, fn () => new WipeCommand);
        }
        foreach (['command.seed', \Illuminate\Database\Console\Seeds\SeedCommand::class] as $abstract) {
            $this->app->extend($abstract, fn ($command, $app) => new SeedCommand($app['db']));
        }
    }
}
