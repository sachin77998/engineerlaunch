<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;

/** A curated list of funds defined by filter rules (High return, Large Cap…). */
class MfCollection extends Model
{
    protected $table = 'mf_collections';
    protected $guarded = ['id'];

    protected $casts = ['rules' => 'array', 'is_active' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
