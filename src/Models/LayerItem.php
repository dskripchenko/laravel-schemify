<?php

namespace Dskripchenko\Schemify\Models;

use Dskripchenko\Schemify\Interfaces\ConnectorInterface;
use Dskripchenko\Schemify\Services\ConnectionHelper;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $layer
 * @property string $name
 * @property string $schema_name
 * @property int $db_connection_id
 */
class LayerItem extends Model implements ConnectorInterface
{
    use SoftDeletes;

    protected $fillable = [
        'layer',
        'name',
        'schema_name',
        'db_connection_id',
    ];

    public function dbConnection(): HasOne
    {
        return $this->hasOne(DbConnection::class, 'id', 'db_connection_id');
    }

    public static function getAllLayerItems($type = null): iterable
    {
        $query = static::query();
        if ($type) {
            $query->where('layer', $type);
        }

        return $query->get();
    }

    /**
     * @return ConnectorInterface|null Null when no layer with the given name exists.
     */
    public static function getLayerItemByName($name): ?ConnectorInterface
    {
        return static::query()->where('name', $name)->first();
    }

    public function refreshConnection(): ConnectionInterface
    {
        $options = array_merge_deep($this->dbConnection->getOptions(), [
            'schema' => $this->schema_name,
        ]);

        return ConnectionHelper::reconnect($options, $this);
    }

    public function getPreparedConnection(ConnectionInterface $connection, $schema): ConnectionInterface
    {
        return ConnectionHelper::getPreparedConnection($connection, $schema);
    }
}
