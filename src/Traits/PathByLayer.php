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
     * Migrations registered by packages (loadMigrationsFrom) go where
     * `schemify.migrations.registered` says:
     *  - 'central' (default) — vanilla behaviour on the central layer; tenant
     *    runs use ONLY the shared tenant set, so admin tables and the like are
     *    not replayed into every layer schema;
     *  - 'tenant' — the other way round, for applications that live entirely
     *    inside a tenant (the panel, its users, its packages);
     *  - 'both' — every run gets them.
     *
     * @return array<int, string>
     */
    protected function getMigrationPaths()
    {
        $registered = (string) config('schemify.migrations.registered', 'central');
        $central = $this->isCentralLayer();

        $withPackages = $registered === 'both'
            || ($registered === 'central' && $central)
            || ($registered === 'tenant' && ! $central);

        // A copy of Migrations\BaseCommand::getMigrationPaths() without
        // parent:: — not every host command extends that base class.
        return $withPackages
            ? array_merge(app('migrator')->paths(), [$this->getMigrationPath()])
            : [$this->getMigrationPath()];
    }
}
