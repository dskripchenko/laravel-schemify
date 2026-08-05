<?php

namespace Dskripchenko\Schemify\Providers;

use Illuminate\Foundation\Providers\ComposerServiceProvider;
use Illuminate\Foundation\Providers\ConsoleSupportServiceProvider as BaseConsoleSupportServiceProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Class ConsoleSupportServiceProvider
 */
class ConsoleSupportServiceProvider extends BaseConsoleSupportServiceProvider
{
    /**
     * The provider class names.
     *
     * @var array<int, class-string<ServiceProvider>>
     */
    protected $providers = [
        ArtisanServiceProvider::class,
        MigrationServiceProvider::class,
        ComposerServiceProvider::class,
    ];
}
