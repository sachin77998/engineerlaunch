<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/** A real-estate developer; projects belong to one. */
class Builder extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['is_active' => 'boolean'];

    public function getRouteKeyName(): string { return 'slug'; }

    public function projects() { return $this->hasMany(Project::class); }

    public function shortDescription(int $words = 40): ?string
    {
        return $this->description ? \Illuminate\Support\Str::words($this->description, $words) : null;
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    }
}
