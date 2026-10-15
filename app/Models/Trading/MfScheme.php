<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;

/** Mutual fund scheme from the AMFI NAV file. */
class MfScheme extends Model
{
    protected $table = 'mf_schemes';
    protected $guarded = ['id'];
    protected $casts = ['nav' => 'float', 'prev_nav' => 'float', 'return_1y' => 'float', 'return_3y' => 'float', 'return_5y' => 'float', 'min_purchase' => 'float', 'min_additional' => 'float', 'nav_date' => 'date', 'is_active' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'scheme_code';
    }

    /** Change since the previous NAV, in percent. */
    public function dayChange(): ?float
    {
        return $this->prev_nav ? round(($this->nav - $this->prev_nav) / $this->prev_nav * 100, 2) : null;
    }
}
