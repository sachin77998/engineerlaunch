<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Model;

/** A school, hospital, metro station… near a spot, fetched from OpenStreetMap and shared by everything at that spot. */
class NearbyPlace extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['fetched_at' => 'datetime'];

    public static function geoKey($lat, $lng): ?string
    {
        return $lat && $lng ? number_format((float) $lat, 3, '.', '') . ',' . number_format((float) $lng, 3, '.', '') : null;
    }

    public function distanceLabel(): string
    {
        return $this->distance_m < 1000 ? $this->distance_m . ' m' : number_format($this->distance_m / 1000, 1) . ' km';
    }
}
