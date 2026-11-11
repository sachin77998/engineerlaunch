<?php

namespace App\Models\Property;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** A resident's review of a locality: overall and feature ratings, positives and negatives. */
class LocalityReview extends Model
{
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function key(?string $locality): string
    {
        return Str::limit(Str::slug((string) $locality), 160, '');
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }

    public function roleLabel(): string
    {
        return config('properties.reviewer_roles.' . $this->reviewer_role, 'Resident');
    }

    /** Overall score shown on the card: the reviewer's stars blended with their feature ratings (one decimal). */
    public function score(): float
    {
        return round(($this->rating * 2 + $this->connectivity + $this->lifestyle + $this->safety + $this->green_area) / 6, 1);
    }
}
