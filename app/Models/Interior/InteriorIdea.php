<?php

namespace App\Models\Interior;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class InteriorIdea extends Model
{
    protected $guarded = [];

    protected $casts = ['is_trending' => 'boolean', 'is_active' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function imageUrl(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }

    public function roomLabel(): ?string { return config('interiors.rooms')[$this->room][0] ?? null; }
    public function styleLabel(): ?string { return config('interiors.styles')[$this->style][0] ?? null; }
    public function categoryLabel(): ?string { return config('interiors.categories')[$this->category] ?? null; }
}
