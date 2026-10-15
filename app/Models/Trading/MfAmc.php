<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Mutual fund house. */
class MfAmc extends Model
{
    protected $table = 'mf_amcs';
    protected $guarded = ['id'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function schemes(): HasMany
    {
        return $this->hasMany(MfScheme::class, 'amc_id');
    }
}
