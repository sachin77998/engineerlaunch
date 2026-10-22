<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HiringTeamMember extends Model
{
    protected $fillable = ['owner_id', 'member_id', 'invited_by', 'email', 'role', 'status', 'token_hash', 'invite_expires_at', 'accepted_at'];
    protected $casts = ['invite_expires_at' => 'datetime', 'accepted_at' => 'datetime'];
    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
