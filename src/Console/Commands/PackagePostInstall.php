<?php

namespace Dskripchenko\Schemify\Console\Commands;

/**
 * Class PackagePostInstall
 * @package Dskripchenko\Schemify\Console\Commands
 */
class PackagePostInstall extends InstallMigrationsCommand
{
    protected $componentName = 'layers';

    protected $signature = 'layers:install';

    protected $description = 'Установка миграций компонента layers';

    protected $deniedLayers = [];

    protected $availableLayers = ['core'];


    protected  function getMigrationsDir(): string
    {
        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR .  'database' . DIRECTORY_SEPARATOR .'migrations';
    }
}
