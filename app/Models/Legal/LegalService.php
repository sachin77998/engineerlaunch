<?php

namespace App\Models\Legal;

use App\Services\Property\PropertyFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LegalService extends Model
{
    protected $guarded = [];

    protected $casts = ['audiences' => 'array', 'includes' => 'array', 'documents' => 'array', 'cities' => 'array', 'is_active' => 'boolean', 'addon_only' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Active services offered in a city, in display order. */
    public static function forCity(string $city)
    {
        return static::where('is_active', true)->where('addon_only', false)->orderBy('sort')->get()
            ->filter(fn ($s) => !$s->cities || collect($s->cities)->contains(fn ($c) => Str::slug($c) === Str::slug($city)))->values();
    }

    public function availableIn(string $city): bool
    {
        return $this->is_active && (!$this->cities || collect($this->cities)->contains(fn ($c) => Str::slug($c) === Str::slug($city)));
    }

    public function priceLabel(): string
    {
        return $this->price ? PropertyFormat::full($this->price) . ($this->price_note ? ' ' . $this->price_note : '') : 'Price on call';
    }

    public function categoryLabel(?string $city = null): string
    {
        return self::categoryName($this->category, $city);
    }

    public static function categoryName(string $key, ?string $city = null): string
    {
        $label = config('legal.categories')[$key][0] ?? ucfirst($key);
        if ($key === 'khata' && $city && ($local = config('legal.khata_names')[$city] ?? null)) return $local . ' services';
        return $label;
    }
}
