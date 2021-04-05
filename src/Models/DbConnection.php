<?php

namespace Dskripchenko\Schemify\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DbConnection
 * @package Dskripchenko\Schemify\Models
 */
class DbConnection extends Model
{
    /**
     * @return array
     */
    public function getOptions(){
        return [
            'driver' => $this->driver,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function layerItems(){
        return $this->hasMany(LayerItem::class, 'db_connection_id', 'id');
    }
}
