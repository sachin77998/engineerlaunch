<?php

namespace App\Models\Legal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// Lawyers and CAs the owner works with; shown on /legal only when added at /admin/legal.
class LegalPartner extends Model
{
    protected $guarded = [];

    protected $casts = ['cities' => 'array', 'is_active' => 'boolean', 'rating' => 'float'];

    public static function forCity(string $city)
    {
        return static::where('is_active', true)->orderBy('sort')->get()
            ->filter(fn ($p) => !$p->cities || collect($p->cities)->contains(fn ($c) => Str::slug($c) === Str::slug($city)))->values();
    }

    public function initials(): string
    {
        return mb_strtoupper(collect(preg_split('/\s+/', preg_replace('/[^\pL\s]/u', '', $this->name)))->filter()->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('')) ?: 'L';
    }
}
