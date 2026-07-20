<?php

declare(strict_types=1);

namespace Dskripchenko\Schemify\Tests;

use Dskripchenko\Schemify\Facades\LayerItemConnector;
use Dskripchenko\Schemify\Providers\SchemifyServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [SchemifyServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['LayerItemConnector' => LayerItemConnector::class];
    }

    protected function defineEnvironment($app): void
    {
        // Fixed key so the encrypted `password` cast has an encrypter to use.
        $app['config']->set('app.key', 'base64:AckfSECXIvnK5r28GVIWUAxmbBSjTsmF0Fs5rWEjRV8=');
    }

    /**
     * Create the package's core tables (db_connections, layer_items) on the
     * active default connection by running the shipped migration directly —
     * bypasses the layer-aware `migrate` command's chicken-and-egg on install.
     */
    protected function createCoreTables(): void
    {
        (require dirname(__DIR__).'/database/migrations/001_create_core_tables_struct.php')->up();
    }
}
