<?php

namespace Dskripchenko\Schemify\Events;

/**
 * Fired after the active layer changes via SchemifyManager::switchTo()
 * (including the switches performed inside use() and provision()).
 *
 * Listen to this to re-scope layer-dependent infrastructure — e.g. set a
 * per-layer cache prefix or a storage path prefix.
 */
final class LayerSwitched
{
    public function __construct(
        public readonly ?string $previous,
        public readonly string $current,
    ) {}
}
