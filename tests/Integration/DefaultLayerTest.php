<?php

declare(strict_types=1);

namespace Dskripchenko\Schemify\Tests\Integration;

use Dskripchenko\Schemify\Facades\Schemify;
use Illuminate\Support\Facades\DB;

class DefaultLayerTest extends IntegrationTestCase
{
    public function test_plain_migrate_defaults_to_the_central_layer(): void
    {
        // Без --layer команда должна вести себя как vanilla migrate (central),
        // а не искать хардкодный слой 'main'.
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
    }

    public function test_tenant_migrate_uses_only_the_tenant_path(): void
    {
        config(['schemify.migrations.path' => __DIR__.'/../fixtures/tenant-migrations']);
        Schemify::provision('paths_layer');

        // Прогон по слою не должен пытаться накатить provider-loaded миграции.
        $this->artisan('migrate', ['--layer' => 'paths_layer', '--force' => true])
            ->assertSuccessful();

        $this->assertTrue(
            DB::connection('layer')
                ->getSchemaBuilder()->hasTable('tenant_probe'),
        );
    }
}
