<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialOrderItem extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['unit_price' => 'float', 'mrp' => 'float', 'total' => 'float'];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
