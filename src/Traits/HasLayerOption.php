<?php

namespace Dskripchenko\Schemify\Traits;

use Symfony\Component\Console\Input\InputOption;

/**
 * Опция `--layer` для команд, которые пакет подменяет своими.
 *
 * Объявляется через `configure()`, а не через `getOptions()`, потому что
 * `getOptions()` Laravel вызывает только у команд без `$signature`. Ядро
 * переводит свои команды на сигнатуру постепенно — в 13.21 так уехали
 * `migrate:fresh`, `migrate:install`, `migrate:status`, `migrate:reset`,
 * `db:seed` и `db:wipe`, — и наш override молча переставал существовать:
 * команда резолвилась наша, а опции у неё не было. `configure()` зовётся
 * из конструктора Symfony всегда и от способа объявления параметров у
 * родителя не зависит.
 */
trait HasLayerOption
{
    protected function configure(): void
    {
        parent::configure();

        // Сигнатура родителя (или наша) дописывается уже после configure(),
        // так что проверка нужна на случай, если опция там всё-таки есть:
        // повторный addOption() у Symfony — исключение, а не перезапись.
        if ($this->getDefinition()->hasOption('layer')) {
            return;
        }

        $this->getDefinition()->addOption(new InputOption(
            'layer',
            null,
            InputOption::VALUE_OPTIONAL,
            'Слой, к которому применяется команда.'
        ));
    }
}
