<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Model;

/** Office / shop facts of a commercial listing: seats, cabins, pre-lease and RERA. */
class PropertyCommercialDetail extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['is_pre_leased' => 'boolean', 'lease_amount' => 'float', 'lease_tenure_years' => 'float'];

    public function property() { return $this->belongsTo(Property::class); }

    /** Annual rental yield of a pre-leased unit on its asking price. */
    public function roi(?float $price): ?float
    {
        if (!$this->is_pre_leased || !$this->lease_amount || !$price) return null;
        return round($this->lease_amount * 12 / $price * 100, 2);
    }

    public function seatsLabel(): ?string
    {
        if (!$this->seats_min && !$this->seats_max) return null;
        return $this->seats_max && $this->seats_max != $this->seats_min ? "{$this->seats_min} - {$this->seats_max} seats" : ($this->seats_min ?: $this->seats_max) . ' seats';
    }
}
