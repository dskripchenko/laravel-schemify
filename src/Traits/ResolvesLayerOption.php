<?php

namespace Dskripchenko\Schemify\Traits;

/**
 * Resolves the --layer console option, defaulting to the configured central
 * layer. Commands no longer hardcode a 'main' default — a plain
 * `php artisan migrate` behaves exactly like vanilla Laravel (central run).
 */
trait ResolvesLayerOption
{
    protected function layerOption(): string
    {
        $value = $this->input->getOption('layer');

        return is_string($value) && $value !== ''
            ? $value
            : (string) config('schemify.central_layer', 'core');
    }

    protected function isCentralLayer(): bool
    {
        return $this->layerOption() === (string) config('schemify.central_layer', 'core');
    }
}
