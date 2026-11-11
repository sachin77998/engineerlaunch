<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** An equity funding round read from funding news. */
class FundingRound extends Model
{
    public const TYPES = ['pre_seed' => 'Pre-seed', 'seed' => 'Seed', 'pre_series_a' => 'Pre-Series A', 'series_a' => 'Series A', 'series_b' => 'Series B', 'series_c' => 'Series C',
        'series_d' => 'Series D', 'series_e' => 'Series E', 'series_f' => 'Series F', 'series_g' => 'Series G', 'series_h' => 'Series H', 'bridge' => 'Bridge', 'growth' => 'Growth',
        'pre_ipo' => 'Pre-IPO', 'debt' => 'Venture debt', 'strategic' => 'Strategic', 'undisclosed' => 'Funding round'];

    protected $guarded = ['id'];
    protected $casts = ['announced_on' => 'date', 'amount' => 'decimal:2', 'amount_usd' => 'decimal:2'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function investors(): BelongsToMany
    {
        return $this->belongsToMany(Investor::class)->withPivot('is_lead')->orderByDesc('funding_round_investor.is_lead');
    }

    public function sources(): BelongsToMany
    {
        return $this->belongsToMany(NewsArticle::class, 'funding_round_sources');
    }

    public function label(): string
    {
        return self::TYPES[$this->round_type] ?? 'Funding round';
    }

    /** "$20 mn", "₹150 Cr", "€5 mn"; null when the amount was not disclosed. */
    public function amountLabel(): ?string
    {
        if ($this->amount === null) return null;
        $v = (float) $this->amount;
        // Drop trailing zero decimals only: "120.0" → "120", "17.30" → "17.3".
        $fmt = fn (float $n, int $dp) => preg_replace('/\.0+$|(\.\d*?)0+$/', '$1', number_format($n, $dp));
        if ($this->currency === 'INR') return '₹' . $fmt($v / 1e7, $v >= 1e9 ? 0 : 1) . ' Cr';
        $symbol = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'SGD' => 'S$', 'AED' => 'AED '][$this->currency] ?? $this->currency . ' ';
        return $v >= 1e9 ? $symbol . $fmt($v / 1e9, 2) . ' bn' : $symbol . $fmt($v / 1e6, 1) . ' mn';
    }
}
