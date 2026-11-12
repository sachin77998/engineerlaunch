<?php

namespace App\Models\Property;

use App\Services\Property\PropertyFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/** One unit configuration of a project: its area, price and floor plan. Units with the same label form a tab ("2 BHK Apartment"). */
class ProjectUnit extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['area_sqft' => 'float', 'price' => 'float', 'possession_on' => 'date'];

    public function project() { return $this->belongsTo(Project::class); }

    public function floorPlanUrl(): ?string { return $this->floor_plan_path ? Storage::disk('public')->url($this->floor_plan_path) : null; }

    public function areaLabel(): string
    {
        return number_format($this->area_sqft) . ' sqft (' . number_format($this->area_sqft / 10.7639, 2) . ' sqm)';
    }

    public function areaTypeLabel(): string { return config('properties.area_types.' . $this->area_type, 'Area'); }

    public function priceLabel(): string { return $this->price ? PropertyFormat::short($this->price) : 'Price on request'; }
}
