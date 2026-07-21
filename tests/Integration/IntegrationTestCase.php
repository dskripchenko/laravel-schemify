<?php

declare(strict_types=1);

namespace Dskripchenko\Schemify\Tests\Integration;

use Dskripchenko\Schemify\Tests\TestCase;
use PDO;
use Throwable;

/**
 * Base for tests that exercise real PostgreSQL schema switching. Connection
 * params come from env (SCHEMIFY_TEST_DB_* with DB_* / CI defaults). The whole
 * suite self-skips when no reachable Postgres is configured, so unit CI and
 * machines without a database stay green.
 */
abstract class IntegrationTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        try {
            $pdo = new PDO(
                sprintf('pgsql:host=%s;port=%s;dbname=%s', static::dbHost(), static::dbPort(), static::dbName()),
                static::dbUser(),
                static::dbPassword(),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 2]
            );
            // Clean slate for each test.
            $pdo->exec('DROP TABLE IF EXISTS layer_items CASCADE');
            $pdo->exec('DROP TABLE IF EXISTS db_connections CASCADE');
            $pdo->exec('DROP TABLE IF EXISTS public.tenant_probe CASCADE');
            // Stale-схемы прошлых прогонов.
            $stale = $pdo->query("select schema_name from information_schema.schemata where schema_name like 'lm\\_%' or schema_name like 'prov\\_%' or schema_name like 'q\\_%' or schema_name = 'paths_layer' or schema_name like 'tenant\\_%' or schema_name like 'workspace\\_%'")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($stale as $schema) {
                $pdo->exec('DROP SCHEMA IF EXISTS "'.$schema.'" CASCADE');
            }
        } catch (Throwable $e) {
            $this->markTestSkipped('PostgreSQL not available for integration tests: '.$e->getMessage());
        }

        $this->createCoreTables();
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $pgsql = [
            'driver' => 'pgsql',
            'host' => static::dbHost(),
            'port' => static::dbPort(),
            'database' => static::dbName(),
            'username' => static::dbUser(),
            'password' => static::dbPassword(),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'sslmode' => 'prefer',
        ];

        $app['config']->set('database.default', 'pgsql');
        $app['config']->set('database.connections.pgsql', $pgsql);
        // The layer connection template Schemify reconfigures on the fly.
        $app['config']->set('database.connections.layer', $pgsql);
        $app['config']->set('database.layer', 'layer');
        $app['config']->set('schemify.connection', 'layer');
    }

    protected static function dbHost(): string
    {
        return getenv('SCHEMIFY_TEST_DB_HOST') ?: (getenv('DB_HOST') ?: '127.0.0.1');
    }

    protected static function dbPort(): string
    {
        return getenv('SCHEMIFY_TEST_DB_PORT') ?: (getenv('DB_PORT') ?: '5432');
    }

    protected static function dbName(): string
    {
        return getenv('SCHEMIFY_TEST_DB_DATABASE') ?: (getenv('DB_DATABASE') ?: 'schemify_test');
    }

    protected static function dbUser(): string
    {
        return getenv('SCHEMIFY_TEST_DB_USERNAME') ?: (getenv('DB_USERNAME') ?: 'postgres');
    }

    protected static function dbPassword(): string
    {
        return getenv('SCHEMIFY_TEST_DB_PASSWORD') ?: (getenv('DB_PASSWORD') ?: 'postgres');
    }
}
