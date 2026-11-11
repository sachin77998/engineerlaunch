<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Model;

class PropertySaleTerm extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['price_negotiable' => 'boolean', 'is_new_project' => 'boolean', 'possession_by' => 'date', 'ideal_for' => 'array'];
}
