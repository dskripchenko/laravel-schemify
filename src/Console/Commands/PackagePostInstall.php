<?php

namespace Dskripchenko\Schemify\Console\Commands;

use Dskripchenko\LaravelCMI\Components\InstallMigrationsCommand;

class PackagePostInstall extends InstallMigrationsCommand
{
    protected $componentName = 'schemify';

    protected $signature = 'cmi:schemify:install';

    protected $description = 'Installing schemify component migrations';

    protected function getMigrationsDir(): string
    {
        return dirname(__DIR__, 3) . '/database/migrations';
    }

    protected function getMigrations(): array
    {
        return [
            '001_create_connections_table.php',
            '002_create_connectors_table.php',
        ];
    }
}
