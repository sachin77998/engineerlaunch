<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Model;

class PropertyCity extends Model
{
    protected $guarded = [];

    protected $casts = ['zones' => 'array', 'is_active' => 'boolean'];

    public function localities() { return $this->hasMany(PropertyLocality::class, 'city_id'); }
}
