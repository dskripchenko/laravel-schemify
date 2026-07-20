<?php

declare(strict_types=1);

namespace Dskripchenko\Schemify\Tests;

use Dskripchenko\Schemify\Models\DbConnection;

class DbConnectionTest extends TestCase
{
    public function test_get_options_maps_connection_attributes(): void
    {
        $connection = (new DbConnection)->forceFill([
            'driver' => 'pgsql',
            'host' => 'db.example.test',
            'port' => '5432',
            'database' => 'app',
            'username' => 'postgres',
            'password' => 'secret',
        ]);

        $this->assertSame([
            'driver' => 'pgsql',
            'host' => 'db.example.test',
            'port' => '5432',
            'database' => 'app',
            'username' => 'postgres',
            'password' => 'secret',
        ], $connection->getOptions());
    }

    public function test_password_is_stored_encrypted(): void
    {
        $connection = (new DbConnection)->forceFill(['password' => 'secret']);

        // The raw stored attribute must be ciphertext, not the plaintext...
        $raw = $connection->getAttributes()['password'];
        $this->assertNotSame('secret', $raw);

        // ...while reads transparently decrypt back to the plaintext.
        $this->assertSame('secret', $connection->password);
    }
}
