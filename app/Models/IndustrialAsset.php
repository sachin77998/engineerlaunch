<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustrialAsset extends Model
{
    protected $guarded = ['id'];

    public function sector()
    {
        return $this->belongsTo(IndustrialSector::class, 'sector_id');
    }
    public function getUrlAttribute(): ?string
    {
        $path = $this->image_url ?: $this->path;
        if (preg_match('~^https://~i', $path)) return $path;
        return str_starts_with($path, 'images/industries/') && !str_contains($path, '..') ? asset($path) : null;
    }
}
