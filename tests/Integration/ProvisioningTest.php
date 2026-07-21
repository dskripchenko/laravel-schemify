<?php

declare(strict_types=1);

namespace Dskripchenko\Schemify\Tests\Integration;

use Dskripchenko\Schemify\Events\LayerSwitched;
use Dskripchenko\Schemify\Facades\Schemify;
use Dskripchenko\Schemify\Models\DbConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;

class ProvisioningTest extends IntegrationTestCase
{
    private function schemaExists(string $schema): bool
    {
        return DB::connection('pgsql')
            ->table('information_schema.schemata')
            ->where('schema_name', $schema)
            ->exists();
    }

    public function test_provision_creates_registry_rows_and_schema(): void
    {
        $layer = Schemify::provision('prov_a', group: 'workspace');

        $this->assertSame('prov_a', $layer->name);
        $this->assertSame('prov_a', $layer->schema_name);
        $this->assertSame('workspace', $layer->layer);
        $this->assertSame(1, DbConnection::query()->count());
        $this->assertTrue($this->schemaExists('prov_a'));
        // Провижининг не оставляет слой активным.
        $this->assertNull(Schemify::current());
    }

    public function test_provision_preserves_active_layer(): void
    {
        Schemify::provision('prov_base');
        Schemify::switchTo('prov_base');

        Schemify::provision('prov_other');

        $this->assertSame('prov_base', Schemify::current());
    }

    public function test_provision_rejects_duplicate_and_bad_schema(): void
    {
        Schemify::provision('prov_dup');

        $this->expectException(InvalidArgumentException::class);
        Schemify::provision('prov_dup');
    }

    public function test_provision_rejects_unsafe_schema(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Schemify::provision('bad', schema: 'a; DROP SCHEMA public');
    }

    public function test_provision_reuses_existing_connection(): void
    {
        $first = Schemify::provision('prov_c1');
        $second = Schemify::provision('prov_c2', connectionId: $first->db_connection_id);

        $this->assertSame($first->db_connection_id, $second->db_connection_id);
        $this->assertSame(1, DbConnection::query()->count());
    }

    public function test_deprovision_drops_schema_and_registry_row(): void
    {
        Schemify::provision('prov_gone');
        $this->assertTrue($this->schemaExists('prov_gone'));

        Schemify::deprovision('prov_gone', dropSchema: true);

        $this->assertFalse($this->schemaExists('prov_gone'));
        $this->assertDatabaseMissing('layer_items', ['name' => 'prov_gone']);
    }

    public function test_deprovision_of_current_layer_forgets_it(): void
    {
        Schemify::provision('prov_cur');
        Schemify::switchTo('prov_cur');

        Schemify::deprovision('prov_cur', dropSchema: true);

        $this->assertNull(Schemify::current());
        $this->assertFalse($this->schemaExists('prov_cur'));
    }

    public function test_switch_dispatches_layer_switched_event(): void
    {
        Schemify::provision('prov_ev');

        Event::fake([LayerSwitched::class]);
        Schemify::switchTo('prov_ev');

        Event::assertDispatched(
            LayerSwitched::class,
            fn (LayerSwitched $e) => $e->previous === null && $e->current === 'prov_ev',
        );
    }
}
