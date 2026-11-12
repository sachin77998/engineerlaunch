<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProjectImage extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['is_cover' => 'boolean'];

    public function project() { return $this->belongsTo(Project::class); }

    public function url(): string { return Storage::disk('public')->url($this->path); }

    public function kindLabel(): string { return config('properties.image_kinds.' . $this->kind, ucfirst($this->kind)); }
}
