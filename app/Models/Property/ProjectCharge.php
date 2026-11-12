<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Model;

/** A cost on top of the base price ("+ Charges"): parking, club membership, deposits… */
class ProjectCharge extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['amount' => 'float'];
}
