<?php

namespace Dskripchenko\Schemify\Providers;

use Illuminate\Foundation\Providers\ComposerServiceProvider;
use \Illuminate\Foundation\Providers\ConsoleSupportServiceProvider as BaseConsoleSupportServiceProvider;

/**
 * Class ConsoleSupportServiceProvider
 * @package Dskripchenko\Schemify\Providers
 */
class ConsoleSupportServiceProvider extends BaseConsoleSupportServiceProvider
{
    /**
     * The provider class names.
     *
     * @var array
     */
    protected $providers = [
        ArtisanServiceProvider::class,
        MigrationServiceProvider::class,
        ComposerServiceProvider::class,
    ];
}
