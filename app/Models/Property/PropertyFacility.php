<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Model;

class PropertyFacility extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];

    public function label(): string { return config('properties.facilities.' . $this->facility . '.0', $this->facility); }

    public function icon(): string { return config('properties.facilities.' . $this->facility . '.1', '✓'); }
}
