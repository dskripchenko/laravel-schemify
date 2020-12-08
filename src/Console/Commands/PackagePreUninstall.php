<?php


namespace Dskripchenko\Schemify\Console\Commands;


use Dskripchenko\LaravelCMI\Components\UninstallMigrationsCommand;

class PackagePreUninstall extends UninstallMigrationsCommand
{
    protected $componentName = 'schemify';

    protected $needAskCopyToSchemify = false;

    protected $signature = 'cmi:schemify:uninstall';

    protected $description = 'Removing schemify component migrations';
}
