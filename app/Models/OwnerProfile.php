<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OwnerProfile extends Model
{
    protected $fillable = ['user_id', 'phone', 'designation'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
