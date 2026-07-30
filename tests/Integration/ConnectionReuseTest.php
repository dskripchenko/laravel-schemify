<?php

declare(strict_types=1);

namespace Dskripchenko\Schemify\Tests\Integration;

use Dskripchenko\Schemify\Facades\Schemify;
use Dskripchenko\Schemify\Services\ConnectionHelper;
use Illuminate\Support\Facades\DB;

/**
 * Переключение слоя не должно стоить нового соединения.
 *
 * Раньше каждое переключение делало purge и подключалось заново — новый TCP и
 * новая аутентификация. В приложении, где слой меняется на каждом запросе, это
 * съедало 12% времени запроса (профилирование printable, 2026-07-30).
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
        // Соединение то же — значит проверка изоляции становится обязательной:
        // если бы search_path не переключился, клиент читал бы чужую схему.
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

    public function test_schema_is_created_once_per_process(): void
    {
        $this->artisan('layers:new', ['name' => 'tenant_reuse_e', '--force' => true])->assertSuccessful();
        $this->artisan('layers:new', ['name' => 'tenant_reuse_f', '--force' => true])->assertSuccessful();

        // Провижининг уже подтвердил существование — считаем только то, что
        // добавят последующие переключения.
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
        // Ради экономии соединения нельзя оставлять путь на чужой схеме:
        // код, который возьмёт подключение слоя без переключения, обязан
        // видеть исходную схему, а не данные последнего клиента.
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

        // Таблицы клиента больше не видно: путь вернулся к исходному.
        $visible = DB::connection($connection)
            ->table('information_schema.tables')
            ->where('table_name', 'leak_probe')
            ->whereRaw('table_schema = current_schema()')
            ->exists();

        $this->assertFalse($visible, 'после forget подключение не должно видеть схему клиента');
    }

    public function test_forgetting_ensured_schema_lets_it_be_created_again(): void
    {
        // Схему могли удалить из-под процесса — тогда подтверждение обязано
        // сбрасываться, иначе она больше никогда не будет создана.
        $this->artisan('layers:new', ['name' => 'tenant_reuse_g', '--force' => true])->assertSuccessful();
        $this->artisan('layers:new', ['name' => 'tenant_reuse_h', '--force' => true])->assertSuccessful();

        // Уходим на другой слой: переключение на уже активный выходит раньше,
        // чем дело доходит до проверки схемы.
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
