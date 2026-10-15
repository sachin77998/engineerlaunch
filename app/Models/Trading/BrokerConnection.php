<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;

/** An investor's own broker login (Zerodha Kite); the access token is encrypted. */
class BrokerConnection extends Model
{
    protected $table = 'broker_connections';
    protected $guarded = ['id'];

    protected $casts = ['access_token' => 'encrypted', 'public_token' => 'encrypted', 'profile' => 'array', 'login_at' => 'datetime', 'expires_at' => 'datetime'];
    protected $hidden = ['access_token', 'public_token'];

    public function isLive(): bool
    {
        return (bool) $this->access_token && (bool) $this->expires_at?->isFuture();
    }
}
