<?php

namespace Dskripchenko\Schemify\Console\Commands;

use Dskripchenko\Schemify\Models\LayerItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * `layers:migrate` applies tenant migrations to every layer in the
 * `layer_items` registry (v3.3: registry-driven; the `layersStruct` config is
 * no longer used). Runs in deployment after the central `migrate`.
 */
class MigrateCommand extends Command
{
    protected $signature = 'layers:migrate
        {--group= : Только слои этой группы (layer_items.layer)}
        {--force : Прокинуть --force в migrate (production)}';

    protected $description = 'Применение tenant-миграций ко всем зарегистрированным слоям';

    public function handle(): int
    {
        if (! Schema::hasTable((new LayerItem)->getTable())) {
            $this->warn('Таблица layer_items отсутствует — реестр слоёв ещё не установлен, пропуск.');

            return self::SUCCESS;
        }

        $query = LayerItem::query()->orderBy('id');
        if (is_string($group = $this->option('group')) && $group !== '') {
            $query->where('layer', $group);
        }

        $names = $query->pluck('name');
        if ($names->isEmpty()) {
            $this->info('Зарегистрированных слоёв нет — нечего мигрировать.');

            return self::SUCCESS;
        }

        $failed = 0;
        foreach ($names as $name) {
            $this->line("→ migrate --layer={$name}");
            $code = Artisan::call('migrate', array_filter([
                '--layer' => $name,
                '--force' => (bool) $this->option('force'),
            ]));
            $this->output->write(Artisan::output());
            if ($code !== 0) {
                $this->error("  слой {$name}: exit {$code}");
                $failed++;
            }
        }

        if ($failed > 0) {
            $this->error("Слоёв с ошибками: {$failed}");

            return self::FAILURE;
        }

        $this->info('Все слои мигрированы ('.$names->count().').');

        return self::SUCCESS;
    }
}
