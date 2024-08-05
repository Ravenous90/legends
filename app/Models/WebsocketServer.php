<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsocketServer extends Model
{
    protected $table = 'websocket_server';

    protected $fillable = [
        'id',
        'url',
        'host',
        'port',
        'status',
        'message',
        'token',
        'command_pid',
    ];
}
