<?php

namespace Dskripchenko\Schemify\Support;

use InvalidArgumentException;

/**
 * Validation and safe quoting for PostgreSQL schema identifiers.
 *
 * Schema names reach raw SQL (CREATE SCHEMA / DROP SCHEMA), so they must never
 * be interpolated unchecked. We restrict them to a conservative identifier
 * shape and double-quote them for the statement.
 */
final class SchemaName
{
    /**
     * Unquoted PostgreSQL identifier: starts with a letter or underscore,
     * followed by letters, digits or underscores. Max 63 bytes (PG limit).
     */
    private const PATTERN = '/^[A-Za-z_][A-Za-z0-9_]{0,62}$/';

    public static function isValid(string $schema): bool
    {
        return (bool) preg_match(self::PATTERN, $schema);
    }

    /**
     * @throws InvalidArgumentException when the schema name is unsafe.
     */
    public static function assertValid(string $schema): string
    {
        if (! self::isValid($schema)) {
            throw new InvalidArgumentException(
                "Invalid schema name '{$schema}'. Allowed: letters, digits, underscore; must start with a letter or underscore; max 63 chars."
            );
        }

        return $schema;
    }

    /**
     * Validate and return a double-quoted identifier safe for interpolation.
     */
    public static function quote(string $schema): string
    {
        return '"'.self::assertValid($schema).'"';
    }
}
