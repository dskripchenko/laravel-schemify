<?php

namespace Dskripchenko\Schemify\Facades;

use Dskripchenko\Schemify\Support\SchemifyManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string|null current()
 * @method static string connectionName()
 * @method static \Illuminate\Database\ConnectionInterface switchTo(string $name)
 * @method static mixed use(string $name, \Closure $callback)
 * @method static void forget()
 * @method static \Dskripchenko\Schemify\Models\LayerItem provision(string $name, ?string $schema = null, ?string $group = null, ?int $connectionId = null, bool $migrate = false)
 * @method static void deprovision(string $name, bool $dropSchema = false)
 *
 * @see SchemifyManager
 */
class Schemify extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'schemify';
    }
}
