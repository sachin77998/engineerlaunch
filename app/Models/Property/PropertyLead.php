<?php

namespace App\Models\Property;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Someone who viewed the owner's number, sent a message or asked for a visit. */
class PropertyLead extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['visit_at' => 'datetime'];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
