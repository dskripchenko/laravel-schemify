<?php

namespace Dskripchenko\Schemify;

use Dskripchenko\Schemify\Console\Commands\PackagePostInstall;
use Dskripchenko\Schemify\Console\Commands\PackagePreUninstall;
use Dskripchenko\Schemify\Interfaces\ConnectorInterface;
use Dskripchenko\Schemify\Models\Connector;
use Dskripchenko\Schemify\Providers\ConsoleSupportServiceProvider;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Support\ServiceProvider;

class SchemifyServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/config/database.php', 'database');

        $this->app->register(ConsoleSupportServiceProvider::class);

        $this->commands(
            [
                PackagePostInstall::class,
                PackagePreUninstall::class
            ]
        );

        $this->app->bind(ConnectorInterface::class, Connector::class);

        $this->app->bind(
            'schemify_connector',
            function ($app) {
                try {
                    return $app->make(ConnectorInterface::class);
                } catch (\Exception $e) {
                    $abstract = ConnectorInterface::class;
                    $message = trans("Implementation not installed") . " {$abstract}.";
                    throw new \Exception($message);
                }
            }
        );

        parent::register();
    }

    /**
     * Merge the given configuration with the existing configuration.
     *
     * @param string $path
     * @param string $key
     * @return void
     */
    protected function mergeConfigFrom($path, $key)
    {
        if (!($this->app instanceof CachesConfiguration && $this->app->configurationIsCached())) {
            $this->app['config']->set(
                $key,
                array_merge_deep(
                    require $path,
                    $this->app['config']->get($key, [])
                )
            );
        }
    }
}
