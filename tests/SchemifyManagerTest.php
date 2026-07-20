<?php

declare(strict_types=1);

namespace Dskripchenko\Schemify\Tests;

use Dskripchenko\Schemify\Support\SchemifyManager;

class SchemifyManagerTest extends TestCase
{
    public function test_manager_is_bound_as_singleton(): void
    {
        $this->assertInstanceOf(SchemifyManager::class, $this->app->make('schemify'));
        $this->assertSame($this->app->make('schemify'), $this->app->make('schemify'));
    }

    public function test_current_is_null_by_default(): void
    {
        $this->assertNull($this->app->make('schemify')->current());
    }

    public function test_connection_name_comes_from_config(): void
    {
        config()->set('schemify.connection', 'layer');

        $this->assertSame('layer', $this->app->make('schemify')->connectionName());
    }

    public function test_switch_to_unknown_layer_throws(): void
    {
        // No layer_items table is needed: resolution short-circuits on the
        // missing layer once a query is attempted, but an unknown name must
        // surface as InvalidArgumentException rather than a null connection.
        $this->createCoreTables();

        $this->expectException(\InvalidArgumentException::class);

        $this->app->make(SchemifyManager::class)->switchTo('does-not-exist');
    }
}
