<?php

namespace Dskripchenko\Schemify\Console\Commands;

use Dskripchenko\Schemify\Models\DbConnection;
use Dskripchenko\Schemify\Models\LayerItem;

/**
 * `layers:install` — one-shot bootstrap:
 *  1. publishes the package config + core migration into the host app,
 *  2. migrates the central layer (creates db_connections / layer_items),
 *  3. ensures the default layer exists.
 *
 * Idempotent and non-interactive-friendly (--force).
 */
class PackagePostInstall extends BaseCommand
{
    protected $signature = 'layers:install {--force : Overwrite published assets and skip prompts}';

    protected $description = 'Install Schemify: publish assets, migrate central tables, create the default layer';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $central = config('schemify.central_layer', 'core');

        $this->call('vendor:publish', ['--tag' => 'schemify-config', '--force' => $force]);
        $this->call('vendor:publish', ['--tag' => 'schemify-migrations', '--force' => $force]);

        // Central tables live on the default connection (no schema switch).
        $this->call('migrate', ['--layer' => $central, '--force' => true]);

        $this->ensureDefaultLayer();

        $this->info('Schemify installed.');

        return self::SUCCESS;
    }

    protected function ensureDefaultLayer(): void
    {
        $name = env('LAYER_MAIN', 'main');

        if (LayerItem::query()->where('name', $name)->exists()) {
            $this->line("Layer '{$name}' already exists — skipping.");

            return;
        }

        // Seed the default layer's connection from the app's default DB config.
        $default = config('database.default');
        $cfg = config("database.connections.{$default}", []);

        $connection = DbConnection::create([
            'driver' => $cfg['driver'] ?? 'pgsql',
            'host' => $cfg['host'] ?? '127.0.0.1',
            'port' => (string) ($cfg['port'] ?? 5432),
            'database' => $cfg['database'] ?? '',
            'username' => $cfg['username'] ?? '',
            'password' => $cfg['password'] ?? '',
        ]);

        LayerItem::create([
            'layer' => $name,
            'name' => $name,
            'schema_name' => $name,
            'db_connection_id' => $connection->id,
        ]);

        $this->info("Created default layer '{$name}'.");
    }
}
