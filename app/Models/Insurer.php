<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Insurer extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['is_public_sector' => 'boolean', 'is_active' => 'boolean', 'claim_settlement_ratio' => 'float',
        'incurred_claim_ratio' => 'float', 'complaints_per_10k' => 'float', 'solvency_ratio' => 'float'];

    public function offers(string $product): bool
    {
        return in_array($product, array_map('trim', explode(',', $this->products)), true);
    }
}
