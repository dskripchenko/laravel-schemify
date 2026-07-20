<?php

namespace Dskripchenko\Schemify\Traits;

/**
 * Trait PathByLayer
 *
 * Resolves the migration path for the active --layer. A SINGLE shared set of
 * tenant migrations (config schemify.migrations.path) is run against every
 * layer's schema — nothing is copied per layer. The central layer uses the
 * standard central migrations path.
 */
trait PathByLayer
{
    /**
     * Get migration path (either specified by '--path' option or the
     * central/tenant location resolved from config).
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        $targetPath = $this->input->getOption('path');
        if (is_string($targetPath) && $targetPath !== '') {
            return ! $this->usingRealPath()
                ? $this->laravel->basePath().'/'.$targetPath
                : $targetPath;
        }

        $layer = $this->input->getOption('layer');

        if ($layer === config('schemify.central_layer', 'core')) {
            return config('schemify.migrations.central_path') ?: database_path('migrations');
        }

        return config('schemify.migrations.path') ?: database_path('migrations/tenant');
    }
}
