<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Fund category (asset class and sub-category) with its SEBI risk level. */
class MfCategory extends Model
{
    protected $table = 'mf_categories';
    protected $guarded = ['id'];

    public function schemes(): HasMany
    {
        return $this->hasMany(MfScheme::class, 'category_id');
    }
}
