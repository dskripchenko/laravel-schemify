<?php

namespace Dskripchenko\Schemify\Exceptions;

use LogicException;

/**
 * A query ran on a connection with an active layer outside any transaction
 * while the search_path is transaction-scoped. Behind a transaction pooler it
 * would have used whatever path the server connection carried.
 */
final class OutsideTransactionException extends LogicException
{
    public static function for(string $connection, string $query): self
    {
        $sql = strlen($query) > 120 ? substr($query, 0, 117).'...' : $query;

        return new self(
            "Connection '{$connection}' has a transaction-scoped layer active, and a query ran outside a transaction: {$sql}. "
            .'Wrap the unit of work in a transaction (Schemify::transaction(), DB::transaction()).'
        );
    }
}
