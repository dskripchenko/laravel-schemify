<?php

namespace Dskripchenko\Schemify\Console\Commands;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Dskripchenko\LaravelApi\Console\Commands\BaseCommand as BaseApiCommand;

/**
 * Class BaseCommand
 * @package Dskripchenko\Schemify\Console\Commands
 */
class BaseCommand extends BaseApiCommand
{
    protected $componentName;

    /**
     * @param string $message
     * @return string
     */
    protected function getNewMigrationName($message = 'Введите название миграции')
    {
        $name = $this->askValid($message, [
            'required',
            'min:3',
            'regex:/^[a-zA-Z_]+$/i',
            function ($attribute, $value, $fail) {
                $className = Str::camel($value);
                if (class_exists($className)) {
                    $fail("{$className} уже существует");
                }
            }
        ]);
        return Str::camel($name);
    }

    /**
     * @param $file
     * @return false|string
     */
    protected function getMigrationClassNameFromFile($file)
    {
        if (!is_file($file)) {
            return false;
        }

        $fileContent = file_get_contents($file);
        $pattern = "/^[\s]*?class[\s]*?(?<class>[\S]+?)[\s][\s\S]*?Migration/m";
        preg_match($pattern, $fileContent, $matches);

        return Arr::get($matches, 'class', false);
    }

    /**
     * @param $className
     * @return bool
     */
    protected function isMigrationClassNameExists($className): bool
    {
        $this->preloadMigrationFiles();
        return class_exists($className);
    }


    protected function preloadMigrationFiles(): void
    {
        static $isMigrationLoaded = false;
        if (!$isMigrationLoaded) {
            $isMigrationLoaded = true;
            $dir = database_path('migrations');
            $closure = function ($dir) use (&$closure) {
                foreach (scandir($dir) as $filename) {
                    $filepath = "{$dir}/{$filename}";
                    if (is_file($filepath)) {
                        require_once $filepath;
                    } else if (is_dir($filepath) && !in_array($filename, ['.', '..'])) {
                        $closure($filepath);
                    }
                }
            };
            $closure($dir);
        }
    }

    /**
     * @param $from
     * @param $to
     */
    protected function copyMigrations($from, $to): void
    {
        if (!is_dir($to)) {
            if (!mkdir($to, 0777, true) && !is_dir($to)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $to));
            }
        }
        $migrationsMap = $this->getMigrationFilePathMap($from, $to);
        foreach ($migrationsMap as $originFile => $targetFile) {
            $className = $this->getMigrationClassNameFromFile($originFile);
            if ($this->isMigrationClassNameExists($className)) {
                $this->error("{$className} из миграции {$originFile} уже существует");
                if (!$this->confirm("Продолжить копирование миграции?", false)) {
                    continue;
                }
            }
            copy($originFile, $targetFile);
        }
    }

    /**
     * @param $dir
     * @return array
     */
    protected function getMigrationsByDir($dir): array
    {
        $dir = rtrim($dir, '/');
        if(!is_dir($dir)) {
            return [];
        }

        $files = array_map(function ($file) use ($dir) {
            return "{$dir}/{$file}";
        }, array_diff(scandir($dir), ['.', '..']));

        return array_filter($files, function ($file) {
            return is_file($file);
        });
    }

    /**
     * @param null $target
     * @return string
     */
    protected function getTargetMigrationsDir($target = null): string
    {
        return  (!$target) ? database_path('migrations') : database_path("migrations/{$target}");
    }

    /**
     * @param $from
     * @param $to
     * @return array
     */
    protected function getMigrationFilePathMap($from, $to): array
    {
        $to = rtrim($to, '/');
        $timestamp = date('Y_m_d_His');
        $map = [];
        foreach ($this->getMigrationsByDir($from) as $filePath) {
            $fileName = basename($filePath);
            $map[$filePath] = "{$to}/{$timestamp}-{$fileName}";
        }
        return $map;
    }

}
