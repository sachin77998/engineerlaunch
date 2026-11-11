<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Model;

/** PG / hostel beds and flatmate rooms: rent per bed by room type, meals, rules. */
class PropertyPgTerm extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['rooms' => 'array', 'meals' => 'array', 'rules' => 'array', 'services' => 'array', 'available_from' => 'date'];

    public function lowestRent(): ?float
    {
        $rents = collect($this->rooms)->pluck('rent')->filter(fn ($v) => $v !== null && $v !== '');
        return $rents->isEmpty() ? null : (float) $rents->min();
    }
}
