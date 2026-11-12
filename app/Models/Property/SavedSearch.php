<?php

namespace App\Models\Property;

use App\Models\User;
use App\Services\Property\PropertySearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** A search a signed-in user saved, with an alert frequency for new matching listings. */
class SavedSearch extends Model
{
    public const FREQUENCIES = ['instant' => 'As soon as a listing is posted', 'daily' => 'Daily summary', 'weekly' => 'Weekly summary', 'off' => 'No email'];
    public const MAX_PER_USER = 20;

    protected $table = 'property_saved_searches';
    protected $guarded = ['id'];
    protected $casts = ['filters' => 'array', 'seen_at' => 'datetime', 'last_sent_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }

    public static function fingerprint(string $mode, ?string $city, array $filters): string
    {
        ksort($filters);
        return sha1(json_encode([$mode, $city, $filters]));
    }

    /** Live listings matching this search. */
    public function matches(): Builder
    {
        return PropertySearch::query($this->mode, $this->city_slug, $this->filters ?? []);
    }

    /** Matches posted after $since that this search has not emailed yet. */
    public function unsent(\DateTimeInterface $since): Builder
    {
        return $this->matches()->where('published_at', '>=', $since)
            ->whereNotIn('properties.id', DB::table('property_alert_sends')->where('saved_search_id', $this->id)->select('property_id'));
    }

    public function newSinceSeen(): int
    {
        return $this->matches()->where('published_at', '>', $this->seen_at ?? $this->created_at)->count();
    }

    public function url(): string
    {
        $q = $this->filters ?? [];
        return route('properties.search', array_filter([$this->mode, $this->city_slug])) . ($q ? '?' . http_build_query($q) : '');
    }

    public function markSent(array $propertyIds): void
    {
        DB::table('property_alert_sends')->insertOrIgnore(array_map(fn ($id) => ['saved_search_id' => $this->id, 'property_id' => $id, 'created_at' => now()], $propertyIds));
    }
}
