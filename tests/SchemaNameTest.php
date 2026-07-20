<?php

declare(strict_types=1);

namespace Dskripchenko\Schemify\Tests;

use Dskripchenko\Schemify\Support\SchemaName;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase as BaseTestCase;

class SchemaNameTest extends BaseTestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function validNames(): array
    {
        return [
            'simple' => ['main'],
            'underscore lead' => ['_tenant'],
            'digits inside' => ['tenant_42'],
            'mixed case' => ['Tenant_A'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidNames(): array
    {
        return [
            'empty' => [''],
            'leading digit' => ['1tenant'],
            'space' => ['tenant a'],
            'dash' => ['tenant-a'],
            'quote injection' => ['a"; DROP SCHEMA public; --'],
            'semicolon' => ['a;b'],
            'dot' => ['public.secret'],
            'too long' => [str_repeat('a', 64)],
        ];
    }

    #[DataProvider('validNames')]
    public function test_valid_names_pass(string $name): void
    {
        $this->assertTrue(SchemaName::isValid($name));
        $this->assertSame($name, SchemaName::assertValid($name));
        $this->assertSame('"'.$name.'"', SchemaName::quote($name));
    }

    #[DataProvider('invalidNames')]
    public function test_invalid_names_are_rejected(string $name): void
    {
        $this->assertFalse(SchemaName::isValid($name));

        $this->expectException(InvalidArgumentException::class);
        SchemaName::quote($name);
    }
}
