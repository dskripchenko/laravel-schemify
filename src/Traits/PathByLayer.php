<?php

namespace Dskripchenko\Schemify\Traits;

/**
 * Trait PathByLayer
 * @package Dskripchenko\Schemify\Console\Components
 */
trait PathByLayer
{
    /**
     * Get migration path (either specified by '--path' option or default location).
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        $targetPath = $this->input->getOption('path');
        if (is_string($targetPath) && $targetPath) {
            return ! $this->usingRealPath()
                ? $this->laravel->basePath().'/'.$targetPath
                : $targetPath;
        }

        $layer = $this->input->getOption('layer');
        return !$this->usingRealPath() ? database_path("migrations/{$layer}") : $layer;
    }
}
