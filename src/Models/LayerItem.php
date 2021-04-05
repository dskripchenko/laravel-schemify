<?php

namespace Dskripchenko\Schemify\Models;

use Dskripchenko\LaravelApi\Exceptions\ApiException;
use Dskripchenko\Schemify\Interfaces\ConnectorInterface;
use Dskripchenko\Schemify\Services\ConnectionHelper;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Class LayerItem
 * @package Dskripchenko\Schemify\Models
 */
class LayerItem extends Model implements ConnectorInterface
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function dbConnection()
    {
        return $this->hasOne(DbConnection::class, 'id', 'db_connection_id');
    }


    /**
     * @return iterable
     */
    public static function getAllLayerItems($type = null):iterable
    {
        $query = static::query();
        if($type) {
            $query->where('layer', $type);
        }
        return $query->get();
    }


    /**
     * @param $name
     * @return ConnectorInterface
     * @throws ApiException
     */
    public static function getLayerItemByName($name):ConnectorInterface
    {
        $connector = static::query()->where('name', $name)->first();

        if (!$connector) {
            throw new ApiException('layer_not_found', "Layer {$name} not found");
        }

        if (! $connector instanceof ConnectorInterface) {
            $class = get_class($connector);
            throw new ApiException('invalid_connector', "Class {$class} isn't ConnectorInterface");
        }

        return $connector;
    }


    /**
     * @return ConnectionInterface
     */
    public function refreshConnection(): ConnectionInterface
    {
        $options = array_merge_deep($this->dbConnection->getOptions(), [
            'schema' => $this->schema_name
        ]);

        return ConnectionHelper::reconnect($options, $this);
    }

    /**
     * @param ConnectionInterface $connection
     * @param $schema
     * @return ConnectionInterface
     */
    public function getPreparedConnection(ConnectionInterface $connection, $schema): ConnectionInterface
    {
        return ConnectionHelper::getPreparedConnection($connection, $schema);
    }

}
