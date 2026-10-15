<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Units a client holds in a scheme. */
class MfHolding extends Model
{
    protected $table = 'mf_holdings';
    protected $guarded = ['id'];
    protected $casts = ['units' => 'float', 'invested' => 'float', 'avg_nav' => 'float'];

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(MfScheme::class, 'scheme_code', 'scheme_code');
    }
}
