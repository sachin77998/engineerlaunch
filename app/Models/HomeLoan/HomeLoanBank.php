<?php

namespace App\Models\HomeLoan;

use Illuminate\Database\Eloquent\Model;

class HomeLoanBank extends Model
{
    protected $guarded = [];

    protected $casts = ['rate_from' => 'float', 'is_active' => 'boolean'];

    public const SORTS = ['rate' => 'Lowest interest', 'tenure' => 'Max tenure', 'funding' => 'Max funding'];

    public static function listed(string $sort = 'rate')
    {
        $q = static::where('is_active', true);
        match ($sort) {
            'tenure' => $q->orderByDesc('max_tenure')->orderBy('rate_from'),
            'funding' => $q->orderByDesc('max_funding')->orderBy('rate_from'),
            default => $q->orderBy('rate_from')->orderBy('sort'),
        };
        return $q->get();
    }

    public function initials(): string
    {
        return mb_strtoupper(collect(preg_split('/\s+/', $this->name))->reject(fn ($w) => in_array(strtolower($w), ['of', 'bank', 'and'], true))->map(fn ($w) => mb_substr($w, 0, 1))->take(3)->implode('')) ?: 'B';
    }

    public function kindLabel(): string
    {
        return $this->kind === 'nbfc' ? 'Housing finance / NBFC' : 'Bank';
    }
}
