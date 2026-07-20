<?php

declare(strict_types=1);

namespace Dskripchenko\Schemify\Tests\Integration;

use Dskripchenko\Schemify\Facades\Schemify;
use Dskripchenko\Schemify\Models\DbConnection;
use Illuminate\Support\Facades\DB;

class LayerLifecycleTest extends IntegrationTestCase
{
    private function schemaExists(string $schema): bool
    {
        return DB::connection('pgsql')
            ->table('information_schema.schemata')
            ->where('schema_name', $schema)
            ->exists();
    }

    public function test_layers_new_creates_registry_rows_and_schema(): void
    {
        $this->artisan('layers:new', ['name' => 'tenant_a', '--force' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('layer_items', ['name' => 'tenant_a', 'schema_name' => 'tenant_a']);
        $this->assertSame(1, DbConnection::query()->count());
        $this->assertTrue($this->schemaExists('tenant_a'), 'schema tenant_a should have been created');
    }

    public function test_new_layer_rejects_unsafe_schema_name(): void
    {
        $this->artisan('layers:new', ['name' => 'bad', '--schema' => 'a; DROP SCHEMA public', '--force' => true])
            ->assertFailed();

        $this->assertDatabaseMissing('layer_items', ['name' => 'bad']);
    }

    public function test_manager_use_switches_and_restores(): void
    {
        $this->artisan('layers:new', ['name' => 'tenant_b', '--force' => true])->assertSuccessful();

        $this->assertNull(Schemify::current());

        $seen = Schemify::use('tenant_b', function () {
            return Schemify::current();
        });

        $this->assertSame('tenant_b', $seen);
        $this->assertNull(Schemify::current(), 'layer must be restored after use()');
    }

    public function test_layers_delete_with_drop_schema_removes_everything(): void
    {
        $this->artisan('layers:new', ['name' => 'tenant_c', '--force' => true])->assertSuccessful();
        $this->assertTrue($this->schemaExists('tenant_c'));

        $this->artisan('layers:delete', ['name' => 'tenant_c', '--drop-schema' => true, '--force' => true])
            ->assertSuccessful();

        $this->assertDatabaseMissing('layer_items', ['name' => 'tenant_c']);
        $this->assertFalse($this->schemaExists('tenant_c'), 'schema tenant_c should have been dropped');
    }

    public function test_layers_list_runs(): void
    {
        $this->artisan('layers:new', ['name' => 'tenant_d', '--force' => true])->assertSuccessful();

        $this->artisan('layers:list')->assertSuccessful();
    }
}
