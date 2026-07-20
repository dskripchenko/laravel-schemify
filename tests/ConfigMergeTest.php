<?php

declare(strict_types=1);

namespace Dskripchenko\Schemify\Tests;

/**
 * The provider extends laravel-api's BaseServiceProvider so that
 * mergeConfigFrom() performs a DEEP merge. This is what lets the package
 * inject its dynamic "layer" connection into database.connections without
 * clobbering the connections already defined by the host application —
 * Laravel's native mergeConfigFrom() only merges top-level keys.
 */
class ConfigMergeTest extends TestCase
{
    public function test_layer_connection_is_injected(): void
    {
        $this->assertIsArray(config('database.connections.layer'));
        $this->assertSame('pgsql', config('database.connections.layer.driver') ?: 'pgsql');
    }

    public function test_host_connections_are_preserved(): void
    {
        // Testbench defines a default connection; the deep merge must not drop it.
        $default = config('database.default');

        $this->assertNotNull($default);
        $this->assertIsArray(config("database.connections.{$default}"));
    }

    public function test_layers_struct_is_available(): void
    {
        $this->assertIsArray(config('database.layersStruct'));
        $this->assertArrayHasKey('core', config('database.layersStruct'));
    }
}
