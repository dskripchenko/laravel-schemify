<?php

namespace Dskripchenko\Schemify\Events;

/**
 * Fired after SchemifyManager::forget() — the app is back on its default
 * connection with no active layer.
 */
final class LayerForgotten
{
    public function __construct(
        public readonly ?string $previous,
    ) {}
}
