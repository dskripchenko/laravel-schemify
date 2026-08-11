<?php

namespace Dskripchenko\Schemify\Traits;

use Symfony\Component\Console\Input\InputOption;

/**
 * The `--layer` option for the commands this package overrides.
 *
 * Declared through `configure()` rather than `getOptions()`, because Laravel
 * only calls `getOptions()` for commands without a `$signature`. The framework
 * migrates its own commands to signatures gradually — 13.21 did so for
 * `migrate:fresh`, `migrate:install`, `migrate:status`, `migrate:reset`,
 * `db:seed` and `db:wipe` — and the override silently stopped existing: the
 * command still resolved to ours, but the option was gone. `configure()` is
 * always called from Symfony's constructor and does not depend on how the
 * parent declares its parameters.
 */
trait HasLayerOption
{
    protected function configure(): void
    {
        parent::configure();

        // The parent's signature (or ours) is applied after configure() runs,
        // so this guard covers the case where the option is already there:
        // a repeated addOption() throws in Symfony rather than overwriting.
        if ($this->getDefinition()->hasOption('layer')) {
            return;
        }

        $this->getDefinition()->addOption(new InputOption(
            'layer',
            null,
            InputOption::VALUE_OPTIONAL,
            'The layer the command applies to.'
        ));
    }
}
