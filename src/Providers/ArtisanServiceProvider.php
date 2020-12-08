<?php


namespace Dskripchenko\Schemify\Providers;


use Dskripchenko\Schemify\Console\Database\SeedCommand;
use Dskripchenko\Schemify\Console\Database\WipeCommand;

class ArtisanServiceProvider extends \Illuminate\Foundation\Providers\ArtisanServiceProvider
{
    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerDbWipeCommand()
    {
        $this->app->singleton(
            'command.db.wipe',
            function () {
                return new WipeCommand; //use custom App\Console\Database\WipeCommand
            }
        );
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerSeedCommand()
    {
        $this->app->singleton(
            'command.seed',
            function ($app) {
                return new SeedCommand($app['db']);
            }
        );
    }

    public function register()
    {
        parent::register();
    }


}
