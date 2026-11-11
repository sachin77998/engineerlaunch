<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** A fund, angel network, corporate or person seen investing in funding rounds. */
class Investor extends Model
{
    public const TYPES = ['vc' => 'Venture capital', 'angel_network' => 'Angel network', 'accelerator' => 'Accelerator', 'corporate' => 'Corporate', 'financial' => 'Bank / financial institution',
        'individual' => 'Individual', 'government' => 'Government', 'firm' => 'Investment firm'];

    protected $guarded = ['id'];
    protected $casts = ['last_invested_on' => 'date'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function rounds(): BelongsToMany
    {
        return $this->belongsToMany(FundingRound::class)->withPivot('is_lead')->orderByDesc('funding_rounds.announced_on');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? 'Investment firm';
    }
}
