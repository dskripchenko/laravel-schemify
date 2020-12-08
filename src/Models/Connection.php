<?php

namespace Dskripchenko\Schemify\Models;

use Illuminate\Database\Eloquent\Model;

class Connection extends Model
{
    protected $fillable = [
        'host',
        'port',
        'database',
        'username',
        'password'
    ];

    protected $visible = [
        'host',
        'port',
        'database',
        'username',
        'password'
    ];

    public function connectors()
    {
        return $this->hasMany(Connector::class);
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
        ];
    }
}
