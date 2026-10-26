<?php

declare(strict_types=1);

namespace Dskripchenko\Schemify\Tests\Integration;

use Dskripchenko\Schemify\Facades\Schemify;
use Dskripchenko\Schemify\Models\LayerItem;
use Illuminate\Support\Facades\DB;

/**
 * A process with a session path (a worker of a host that pools only its web
 * role) reaches the layer with the `direct` options, whatever the registry
 * row says: a session SET must never go through a transaction pooler.
 */
class SessionDirectTest extends IntegrationTestCase
{
    public function test_a_session_path_connection_uses_the_direct_options(): void
    {
        $this->artisan('layers:new', ['name' => 'tenant_sd_a', '--force' => true])->assertSuccessful();
        Schemify::use('tenant_sd_a', fn () => DB::connection((string) config('database.layer'))->statement('CREATE TABLE probe (marker text)'));
        Schemify::forget();

        // The registry points at "the pooler" — a port nothing listens on.
        $row = LayerItem::query()->where('name', 'tenant_sd_a')->firstOrFail()->dbConnection()->firstOrFail();
        $row->setAttribute('port', '1');
        $row->save();
        DB::purge((string) config('database.layer'));

        config(['schemify.search_path.direct' => ['port' => static::dbPort()]]);

        $count = Schemify::use('tenant_sd_a', fn () => DB::connection((string) config('database.layer'))->table('probe')->count());

        $this->assertSame(0, $count);
        $this->assertSame(static::dbPort(), (string) DB::connection((string) config('database.layer'))->getConfig('port'));
    }
}
