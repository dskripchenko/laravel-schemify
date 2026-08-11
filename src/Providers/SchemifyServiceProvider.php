<?php

namespace Dskripchenko\Schemify\Providers;

use Dskripchenko\LaravelApi\Providers\BaseServiceProvider;
use Dskripchenko\Schemify\Console\Commands\ApiInstall;
use Dskripchenko\Schemify\Console\Commands\DeleteLayerCommand;
use Dskripchenko\Schemify\Console\Commands\ListLayersCommand;
use Dskripchenko\Schemify\Console\Commands\MigrateCommand;
use Dskripchenko\Schemify\Console\Commands\NewLayerCommand;
use Dskripchenko\Schemify\Console\Commands\PackagePostInstall;
use Dskripchenko\Schemify\Interfaces\ConnectorInterface;
use Dskripchenko\Schemify\Models\LayerItem;
use Dskripchenko\Schemify\Queue\LayerPropagator;
use Dskripchenko\Schemify\Support\SchemifyManager;

/**
 * Class SchemifyServiceProvider
 */
class SchemifyServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                dirname(__DIR__, 2).'/config/schemify.php' => config_path('schemify.php'),
            ], 'schemify-config');

            $this->publishes([
                dirname(__DIR__, 2).'/database/migrations/001_create_core_tables_struct.php' => database_path('migrations/2020_01_01_000000_create_schemify_core_tables.php'),
            ], 'schemify-migrations');
        }

        if (config('schemify.queue.propagate')) {
            LayerPropagator::register();
        }

        $this->commands([
            PackagePostInstall::class,
            MigrateCommand::class,
            NewLayerCommand::class,
            ListLayersCommand::class,
            DeleteLayerCommand::class,
            ApiInstall::class,
        ]);
    }

    public function register()
    {
        $this->mergeConfigFrom(dirname(__DIR__, 2).'/config/database.php', 'database');

        $this->mergeConfigFrom(dirname(__DIR__, 2).'/config/schemify.php', 'schemify');

        $this->app->register(ConsoleSupportServiceProvider::class);

        $this->app->singleton(SchemifyManager::class);
        $this->app->alias(SchemifyManager::class, 'schemify');

        $this->app->bind(ConnectorInterface::class, LayerItem::class);
        $this->app->bind('layer_item_connector', function ($app) {
            try {
                return $app->make(ConnectorInterface::class);
            } catch (\Exception $e) {
                $abstract = ConnectorInterface::class;
                throw new \Exception("No implementation bound for {$abstract}.");
            }
        });

        parent::register();
    }
}
