<?php

namespace Dskripchenko\Schemify\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Class Automigrate
 * @package Dskripchenko\Schemify\Console\Commands
 */
class MigrateCommand extends Command
{
    protected $name = 'layers:migrate';

    protected $description = 'Применение миграций по всем слоям';

    public function handle()
    {
        $this->applyMigrations();
    }

    protected function applyMigrations(): void
    {
        $closure = function ($layers = []) use (&$closure) {
            if (!is_array($layers)) {
                return;
            }

            foreach ($layers as $layer => $value) {
                // пропускаем все выключенные в конфиге слои
                if (!$value) {
                    continue;
                }

                Artisan::call("migrate", ['--layer' => $layer]);
                $closure($value);
            }
        };
        $layers = config('database.layersStruct', []);
        $closure($layers);
    }
}
