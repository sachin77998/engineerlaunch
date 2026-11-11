<?php

namespace App\Models\HomeService;

use App\Services\Property\PropertyFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HomeService extends Model
{
    protected $guarded = [];

    protected $casts = ['includes' => 'array', 'cities' => 'array', 'is_active' => 'boolean'];

    public static function forCity(string $city)
    {
        return static::where('is_active', true)->orderBy('sort')->get()
            ->filter(fn ($s) => !$s->cities || collect($s->cities)->contains(fn ($c) => Str::slug($c) === Str::slug($city)))->values();
    }

    /** Id of a live service by slug (one query per request), for links that pre-add a service. */
    public static function idFor(string $slug): ?int
    {
        static $ids;
        $ids ??= static::where('is_active', true)->pluck('id', 'slug');
        return $ids[$slug] ?? null;
    }

    public function priceLabel(): string
    {
        return $this->price ? PropertyFormat::full($this->price) . ' ' . $this->unit : 'Price after inspection';
    }
}
