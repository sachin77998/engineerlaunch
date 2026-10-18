<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UsListedCompany extends Model
{
    protected $guarded = [];
    protected $casts = ['price_date' => 'date', 'last_price' => 'float', 'change_pct' => 'float', 'market_cap' => 'float'];

    /** Annual results filed with the SEC (USD), oldest first. */
    public function financials(): HasMany
    {
        return $this->hasMany(UsCompanyFinancial::class, 'cik', 'cik')->orderBy('fiscal_year');
    }

    public function edgarUrl(): ?string
    {
        return $this->cik ? 'https://www.sec.gov/cgi-bin/browse-edgar?action=getcompany&CIK=' . str_pad((string) $this->cik, 10, '0', STR_PAD_LEFT) : null;
    }
}
