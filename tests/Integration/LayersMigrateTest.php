<?php

declare(strict_types=1);

namespace Dskripchenko\Schemify\Tests\Integration;

use Dskripchenko\Schemify\Facades\Schemify;
use Illuminate\Support\Facades\DB;

class LayersMigrateTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['schemify.migrations.path' => __DIR__.'/../fixtures/tenant-migrations']);
    }

    private function probeExists(string $schema): bool
    {
        return DB::connection('pgsql')
            ->table('information_schema.tables')
            ->where('table_schema', $schema)
            ->where('table_name', 'tenant_probe')
            ->exists();
    }

    public function test_migrates_every_registered_layer(): void
    {
        Schemify::provision('lm_a', group: 'ws');
        Schemify::provision('lm_b', group: 'other');

        $this->artisan('layers:migrate', ['--force' => true])->assertSuccessful();

        $this->assertTrue($this->probeExists('lm_a'));
        $this->assertTrue($this->probeExists('lm_b'));
    }

    public function test_group_filter_limits_the_run(): void
    {
        Schemify::provision('lm_g1', group: 'ws');
        Schemify::provision('lm_g2', group: 'other');

        $this->artisan('layers:migrate', ['--group' => 'ws', '--force' => true])
            ->assertSuccessful();

        $this->assertTrue($this->probeExists('lm_g1'));
        $this->assertFalse($this->probeExists('lm_g2'));
    }
}
