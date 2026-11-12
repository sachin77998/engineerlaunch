<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Model;

class PropertyLocality extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean', 'latitude' => 'float', 'longitude' => 'float'];

    public function city() { return $this->belongsTo(PropertyCity::class, 'city_id'); }
}
