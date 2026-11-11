<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Model;

class PropertyRentTerm extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['rent_negotiable' => 'boolean', 'maintenance_extra' => 'boolean', 'deposit_negotiable' => 'boolean', 'available_from' => 'date',
        'preferred_tenants' => 'array', 'ideal_for' => 'array'];
}
