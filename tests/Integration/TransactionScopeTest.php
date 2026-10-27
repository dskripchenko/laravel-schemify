<?php

declare(strict_types=1);

namespace Dskripchenko\Schemify\Tests\Integration;

use Dskripchenko\Schemify\Exceptions\OutsideTransactionException;
use Dskripchenko\Schemify\Facades\Schemify;
use Dskripchenko\Schemify\Models\LayerItem;
use Illuminate\Support\Facades\DB;
use PDO;

/**
 * The transaction-scoped search_path: a layer reaches the server only with SET
 * LOCAL inside transactions, so a pooler in transaction mode never hands a
 * tenant's path to another client.
 */
class TransactionScopeTest extends IntegrationTestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('schemify.search_path.scope', 'transaction');
    }

    private function layer(): string
    {
        return (string) config('database.layer');
    }

    private function makeLayers(string ...$names): void
    {
        foreach ($names as $name) {
            $this->artisan('layers:new', ['name' => $name, '--force' => true])->assertSuccessful();
            Schemify::transaction($name, function () use ($name): void {
                DB::connection($this->layer())->statement('CREATE TABLE probe (marker text)');
                DB::connection($this->layer())->table('probe')->insert(['marker' => $name]);
            });
        }
    }

    public function test_a_transaction_reads_its_layer(): void
    {
        $this->makeLayers('tenant_ts_a', 'tenant_ts_b');

        $a = Schemify::transaction('tenant_ts_a', fn () => DB::connection($this->layer())->table('probe')->value('marker'));
        $b = Schemify::transaction('tenant_ts_b', fn () => DB::connection($this->layer())->table('probe')->value('marker'));

        $this->assertSame('tenant_ts_a', $a);
        $this->assertSame('tenant_ts_b', $b);
    }

    public function test_a_query_outside_a_transaction_is_refused(): void
    {
        $this->makeLayers('tenant_ts_c');
        Schemify::switchTo('tenant_ts_c');

        $this->expectException(OutsideTransactionException::class);

        DB::connection($this->layer())->table('probe')->value('marker');
    }

    public function test_the_path_does_not_outlive_the_transaction(): void
    {
        $this->makeLayers('tenant_ts_d');

        Schemify::transaction('tenant_ts_d', fn () => null);
        Schemify::forget();

        $path = DB::connection($this->layer())->selectOne('show search_path')->search_path;

        $this->assertStringNotContainsString('tenant_ts_d', $path);
    }

    public function test_switching_inside_a_transaction_applies_at_once(): void
    {
        $this->makeLayers('tenant_ts_e', 'tenant_ts_f');

        $markers = Schemify::transaction('tenant_ts_e', function (): array {
            $first = DB::connection($this->layer())->table('probe')->value('marker');
            $second = Schemify::use('tenant_ts_f', fn () => DB::connection($this->layer())->table('probe')->value('marker'));
            $back = DB::connection($this->layer())->table('probe')->value('marker');

            return [$first, $second, $back];
        });

        $this->assertSame(['tenant_ts_e', 'tenant_ts_f', 'tenant_ts_e'], $markers);
    }

    public function test_appended_schemas_follow_the_layer(): void
    {
        config(['schemify.search_path.append' => ['public']]);
        $this->makeLayers('tenant_ts_g');

        $path = Schemify::transaction('tenant_ts_g', fn () => DB::connection($this->layer())->selectOne('show search_path')->search_path);

        $this->assertSame('"tenant_ts_g", "public"', $path);
    }

    public function test_layer_migrations_run_on_a_session_and_leave_no_path(): void
    {
        $this->artisan('layers:new', ['name' => 'tenant_ts_m', '--force' => true])->assertSuccessful();

        $this->artisan('migrate', [
            '--layer' => 'tenant_ts_m',
            '--path' => realpath(__DIR__.'/../fixtures/tenant-migrations'),
            '--realpath' => true,
            '--force' => true,
        ])->assertSuccessful();

        $tables = DB::connection('pgsql')->table('information_schema.tables')
            ->where('table_schema', 'tenant_ts_m')->pluck('table_name')->all();
        $this->assertContains('migrations', $tables);

        $path = DB::connection($this->layer())->selectOne('show search_path')->search_path;
        $this->assertStringNotContainsString('tenant_ts_m', $path);
    }

    public function test_the_registry_uses_the_central_connection(): void
    {
        config(['schemify.central_connection' => 'pgsql']);

        $this->assertSame('pgsql', (new LayerItem)->getConnectionName());
    }

    /**
     * Through a real PgBouncer in transaction mode (SCHEMIFY_TEST_POOLER_PORT):
     * after tenant transactions, no pooled server connection carries a tenant
     * path.
     */
    public function test_no_tenant_path_leaks_through_a_transaction_pooler(): void
    {
        $port = getenv('SCHEMIFY_TEST_POOLER_PORT');
        if (! $port) {
            $this->markTestSkipped('Set SCHEMIFY_TEST_POOLER_PORT to a PgBouncer in transaction mode.');
        }

        // Both connections go through the pooler: layers:new copies the
        // default connection into the registry, and that copy is what the
        // layer connects with.
        foreach (['pgsql', $this->layer()] as $name) {
            config(["database.connections.{$name}.port" => $port]);
            config(["database.connections.{$name}.options" => [PDO::PGSQL_ATTR_DISABLE_PREPARES => true]]);
            DB::purge($name);
        }

        $this->makeLayers('tenant_ts_x', 'tenant_ts_y');

        for ($i = 0; $i < 20; $i++) {
            Schemify::transaction($i % 2 ? 'tenant_ts_x' : 'tenant_ts_y', fn () => DB::connection($this->layer())->table('probe')->count());
        }

        // The layer stays active on purpose: what matters is the server side.

        $seen = [];
        for ($i = 0; $i < 40; $i++) {
            $pdo = new PDO(
                sprintf('pgsql:host=%s;port=%s;dbname=%s', static::dbHost(), $port, static::dbName()),
                static::dbUser(),
                static::dbPassword(),
            );
            $seen[] = (string) $pdo->query('show search_path')->fetchColumn();
        }

        foreach (array_unique($seen) as $path) {
            $this->assertStringNotContainsString('tenant_ts_', $path);
        }
    }
}
