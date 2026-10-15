<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function fundHouse(): BelongsTo
    {
        return $this->belongsTo(MfAmc::class, 'amc_id');
    }

    public function categoryRow(): BelongsTo
    {
        return $this->belongsTo(MfCategory::class, 'category_id');
    }

    /** Name as shown in lists: "HDFC Flexi Cap Fund Direct-Growth". */
    public function displayName(): string
    {
        return trim($this->prettyName() . ' ' . trim(($this->plan ?? '') . ($this->option ? '-' . $this->option : ''), '-'));
    }

    /** Fund name in title case when AMFI lists it in capitals ("BANK OF INDIA SMALL CAP FUND" → "Bank of India Small Cap Fund"). */
    public function prettyName(): string
    {
        $name = (string) $this->name;
        $letters = preg_replace('/[^A-Za-z]/', '', $name);
        if ($letters === '' || preg_match_all('/[A-Z]/', $letters) / strlen($letters) < 0.8) return $name;
        $out = ucwords(strtolower($name));
        $keep = ['Etf' => 'ETF', 'Fof' => 'FoF', 'Idcw' => 'IDCW', 'Hdfc' => 'HDFC', 'Icici' => 'ICICI', 'Sbi' => 'SBI', 'Uti' => 'UTI', 'Dsp' => 'DSP', 'Psu' => 'PSU',
            'Elss' => 'ELSS', 'Lic' => 'LIC', 'Idfc' => 'IDFC', 'Boi' => 'BOI', 'Hsbc' => 'HSBC', 'Jm ' => 'JM ', 'Pgim' => 'PGIM', 'Iti' => 'ITI', 'Nj ' => 'NJ ', 'Of ' => 'of ', 'And ' => 'and ', 'Us ' => 'US ', 'Nasdaq' => 'NASDAQ', 'Bse' => 'BSE', 'Nse' => 'NSE', 'Msci' => 'MSCI', 'Crisil' => 'CRISIL', 'Ibx' => 'IBX', 'Sdl' => 'SDL', 'G-sec' => 'G-Sec'];
        foreach ($keep as $a => $b) $out = preg_replace('/' . preg_quote(trim($a), '/') . '/', trim($b), $out);
        return $out;
    }
}
