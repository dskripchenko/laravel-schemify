<?php

namespace Dskripchenko\Schemify\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $driver
 * @property string $host
 * @property string $port
 * @property string $database
 * @property string $username
 * @property string $password
 */
class DbConnection extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'driver',
        'host',
        'port',
        'database',
        'username',
        'password',
    ];

    protected $casts = [
        'password' => 'encrypted',
    ];

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return [
            'driver' => $this->driver,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
        ];
    }

    public function layerItems(): HasMany
    {
        return $this->hasMany(LayerItem::class, 'db_connection_id', 'id');
    }
}
