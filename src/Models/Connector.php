<?php

namespace Dskripchenko\Schemify\Models;

use Dskripchenko\Schemify\Interfaces\ConnectorInterface;
use Dskripchenko\Schemify\Services\ConnectionHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Connector extends Model implements ConnectorInterface
{
    protected $fillable = [
        'connection_id',
        'schema'
    ];

    protected $visible = [
        'connection_id',
        'schema'
    ];

    /**
     * @return HasOne
     */
    public function schemifyConnection()
    {
        return $this->hasOne(Connection::class, 'id', 'connection_id');
    }

    /**
     * @param $id
     * @return ConnectorInterface
     */
    public static function getConnectorById($id): ConnectorInterface
    {
        return static::find($id);
    }

    /**
     * @return iterable
     */
    public static function getAllConnectors(): iterable
    {
        return static::query()->get();
    }

    /**
     * @param string $connectionName
     */
    public function refreshConnection(string $connectionName): void
    {
        $options = array_merge_deep(
            $this->schemifyConnection->getOptions(),
            [
                'schema' => $this->schema
            ]
        );

        ConnectionHelper::reconnect(config('database.schemify'), $options);
    }
}
