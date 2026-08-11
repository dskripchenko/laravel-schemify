<?php

namespace Dskripchenko\Schemify\Traits;

/**
 * Trait PathByLayer
 *
 * Resolves the migration path(s) for the active --layer. A SINGLE shared set
 * of tenant migrations (config schemify.migrations.path) is run against every
 * layer's schema — nothing is copied per layer. The central layer uses the
 * standard central migrations path.
 */
trait PathByLayer
{
    use ResolvesLayerOption;

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

        if ($this->isCentralLayer()) {
            return config('schemify.migrations.central_path') ?: database_path('migrations');
        }

        return config('schemify.migrations.path') ?: database_path('migrations/tenant');
    }

    /**
     * Central runs keep vanilla behaviour (provider-registered paths from
     * loadMigrationsFrom + the central path). Tenant runs use ONLY the shared
     * tenant set — vendor/package migrations (admin tables etc.) must not be
     * replayed into every layer schema.
     *
     * @return array<int, string>
     */
    protected function getMigrationPaths()
    {
        if ($this->isCentralLayer()) {
            // A copy of Migrations\BaseCommand::getMigrationPaths() without
            // parent:: — not every host command extends that base class.
            return array_merge(
                app('migrator')->paths(),
                [$this->getMigrationPath()],
            );
        }

        return [$this->getMigrationPath()];
    }
}
