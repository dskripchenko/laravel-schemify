<?php

namespace Dskripchenko\Schemify\Console\Commands;

use Illuminate\Support\Facades\Artisan;

/**
 * Class InstallMigrationsCommand
 * @package Dskripchenko\LaravelCMI\Components
 */
abstract class InstallMigrationsCommand extends BaseCommand
{
    protected $signature = 'component:install';

    protected $description = "Installing component migrations";

    protected $deniedLayers = ['core'];

    protected $availableLayers = null;


    public function handle()
    {
        $this->installMigrations();
    }

    /**
     * @return string
     */
    abstract protected function getMigrationsDir(): string;

    protected function installMigrations(): void
    {
        $closure = function ($layers = []) use (&$closure){
            if(!is_array($layers)) {
                return;
            }
            foreach ($layers as $layer => $value) {
                // пропускаем все выключенные в конфиге слои
                if(!$value) {
                    continue;
                }
                // пропускаем все слои из списка запрещенных
                if(is_array($this->deniedLayers) && in_array($layer, $this->deniedLayers, true)) {
                    $closure($value);
                    continue;
                }
                // пропускаем все слои, которых нет в списке разрешенных
                if(is_array($this->availableLayers) && !in_array($layer, $this->availableLayers, true)) {
                    $closure($value);
                    continue;
                }

                $this->setupMigrations($layer);
                $closure($value);
            }
        };
        $layers = config('database.layersStruct', []);
        $closure($layers);
    }

    /**
     * @param $layer
     */
    protected function setupMigrations($layer): void
    {
        $fromDir = $this->getMigrationsDir();
        $toDir = $this->getTargetMigrationsDir($layer);
        $question = "Copy component migrations {$this->componentName} from {$fromDir} to {$toDir}?";

        if ($this->confirm($question, true)) {
            $this->copyMigrations($fromDir, $toDir);
            if ($this->confirm("Apply component migrations {$this->componentName} to layer {$layer}?", true)) {
                Artisan::call("migrate", ['--layer' => $layer]);
            }
        }
    }
}
