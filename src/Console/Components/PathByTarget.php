<?php


namespace Dskripchenko\Schemify\Console\Components;


trait PathByTarget
{
    /**
     * Get migration path (either specified by '--path' option or default location).
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        $target = $this->input->getOption('target');
        if ($target === 'main') {
            return !$this->usingRealPath() ? database_path('migrations') : 'migrations';
        }

        if ($target === 'schemify' || preg_match('/schemify:[\d]+?/', $target)) {
            return !$this->usingRealPath() ? database_path('migrations/schemify') : 'migrations/schemify';
        }

        return parent::getMigrationPath();
    }
}
