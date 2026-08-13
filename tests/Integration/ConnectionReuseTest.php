<?php

declare(strict_types=1);

namespace Dskripchenko\Schemify\Tests\Integration;

use Dskripchenko\Schemify\Facades\Schemify;
use Dskripchenko\Schemify\Services\ConnectionHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Switching a layer must not cost a new connection.
 *
 * Every switch used to purge and connect anew — a new TCP session and a new
 * authentication. In an application where the layer changes on every request
 * that ate 12% of the request's time (profiling printable, 2026-07-30).
 */
class ConnectionReuseTest extends IntegrationTestCase
{
    private function layerConnectionName(): string
    {
        return (string) config('database.layer');
    }

    public function test_switching_schema_keeps_the_same_connection(): void
    {
        $this->artisan('layers:new', ['name' => 'tenant_reuse_a', '--force' => true])->assertSuccessful();
        $this->artisan('layers:new', ['name' => 'tenant_reuse_b', '--force' => true])->assertSuccessful();

        Schemify::switchTo('tenant_reuse_a');
        $pdoBefore = DB::connection($this->layerConnectionName())->getPdo();

        Schemify::switchTo('tenant_reuse_b');
        $pdoAfter = DB::connection($this->layerConnectionName())->getPdo();

        $this->assertSame(
            $pdoBefore,
            $pdoAfter,
            'смена схемы не должна пересоздавать соединение',
        );
    }

    public function test_switching_schema_actually_changes_search_path(): void
    {
        // The connection is the same, which makes the isolation check
        // mandatory: had the search_path not switched, a client would be reading
        // someone else's schema.
        $this->artisan('layers:new', ['name' => 'tenant_reuse_c', '--force' => true])->assertSuccessful();
        $this->artisan('layers:new', ['name' => 'tenant_reuse_d', '--force' => true])->assertSuccessful();

        $connection = $this->layerConnectionName();

        Schemify::switchTo('tenant_reuse_c');
        DB::connection($connection)->unprepared('CREATE TABLE probe (marker text)');
        DB::connection($connection)->table('probe')->insert(['marker' => 'c']);

        Schemify::switchTo('tenant_reuse_d');
        DB::connection($connection)->unprepared('CREATE TABLE probe (marker text)');
        DB::connection($connection)->table('probe')->insert(['marker' => 'd']);

        $this->assertSame('d', DB::connection($connection)->table('probe')->value('marker'));

        Schemify::switchTo('tenant_reuse_c');
        $this->assertSame('c', DB::connection($connection)->table('probe')->value('marker'));
    }

    public function test_schema_introspection_follows_the_current_layer(): void
    {
        // Queries travel by the search_path, while Postgres's schema builder on
        // Laravel 11/12 takes the schema from the connection's config. While a
        // layer was switched through a purge, the config was recreated together
        // with the instance; with an in-place switch it stayed behind from the
        // previous layer, and `Schema::hasTable()` answered about someone else's
        // schema. Outwardly that surfaced like this: `migrate:install` saw the
        // previous layer's `migrations`, did not create the table for the
        // current one, and `layers:migrate` failed on the second layer.
        $this->artisan('layers:new', ['name' => 'tenant_probe_a', '--force' => true])->assertSuccessful();
        $this->artisan('layers:new', ['name' => 'tenant_probe_b', '--force' => true])->assertSuccessful();

        $connection = $this->layerConnectionName();

        Schemify::switchTo('tenant_probe_a');
        DB::connection($connection)->unprepared('CREATE TABLE only_in_a (id int)');

        Schemify::switchTo('tenant_probe_b');

        $this->assertFalse(
            Schema::connection($connection)->hasTable('only_in_a'),
            'интроспекция обязана смотреть в текущий слой, а не в предыдущий',
        );

        Schemify::switchTo('tenant_probe_a');

        $this->assertTrue(
            Schema::connection($connection)->hasTable('only_in_a'),
            'вернувшись в слой, интроспекция обязана снова видеть его таблицы',
        );
    }

    public function test_schema_is_created_once_per_process(): void
    {
        $this->artisan('layers:new', ['name' => 'tenant_reuse_e', '--force' => true])->assertSuccessful();
        $this->artisan('layers:new', ['name' => 'tenant_reuse_f', '--force' => true])->assertSuccessful();

        // The provisioning has already confirmed existence — we count only what
        // the subsequent switches add.
        $statements = [];
        DB::listen(function ($query) use (&$statements): void {
            if (stripos($query->sql, 'CREATE SCHEMA') !== false) {
                $statements[] = $query->sql;
            }
        });

        Schemify::switchTo('tenant_reuse_e');
        Schemify::switchTo('tenant_reuse_f');
        Schemify::switchTo('tenant_reuse_e');
        Schemify::switchTo('tenant_reuse_f');

        $this->assertSame(
            [],
            $statements,
            'CREATE SCHEMA не должен повторяться на каждом переключении слоя',
        );
    }

    public function test_forget_returns_to_the_default_schema_without_dropping_the_connection(): void
    {
        // Saving a connection is no reason to leave the path on someone else's
        // schema: code that takes the layer's connection without switching must
        // see the original schema rather than the last client's data.
        $this->artisan('layers:new', ['name' => 'tenant_reuse_i', '--force' => true])->assertSuccessful();

        $connection = $this->layerConnectionName();

        Schemify::switchTo('tenant_reuse_i');
        DB::connection($connection)->unprepared('CREATE TABLE leak_probe (marker text)');
        DB::connection($connection)->table('leak_probe')->insert(['marker' => 'клиентские данные']);

        $pdoBefore = DB::connection($connection)->getPdo();
        Schemify::forget();

        $this->assertNull(Schemify::current());
        $this->assertSame(
            $pdoBefore,
            DB::connection($connection)->getPdo(),
            'forget не должен разрывать соединение',
        );

        // The client's table is no longer visible: the path is back to the original.
        $visible = DB::connection($connection)
            ->table('information_schema.tables')
            ->where('table_name', 'leak_probe')
            ->whereRaw('table_schema = current_schema()')
            ->exists();

        $this->assertFalse($visible, 'после forget подключение не должно видеть схему клиента');
    }

    public function test_forgetting_ensured_schema_lets_it_be_created_again(): void
    {
        // The schema may have been dropped from under the process — then the
        // confirmation must be reset, otherwise it would never be created
        // again.
        $this->artisan('layers:new', ['name' => 'tenant_reuse_g', '--force' => true])->assertSuccessful();
        $this->artisan('layers:new', ['name' => 'tenant_reuse_h', '--force' => true])->assertSuccessful();

        // We move to a different layer: a switch to the already active one
        // returns before it ever gets to the schema check.
        Schemify::switchTo('tenant_reuse_h');
        ConnectionHelper::forgetEnsuredSchemas('tenant_reuse_g');

        $statements = [];
        DB::listen(function ($query) use (&$statements): void {
            if (stripos($query->sql, 'CREATE SCHEMA') !== false) {
                $statements[] = $query->sql;
            }
        });

        Schemify::switchTo('tenant_reuse_g');

        $this->assertCount(1, $statements);
    }
}
