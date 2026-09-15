<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustrialState extends Model
{
    use \App\Models\Concerns\HasIndustrialSource;

    protected $fillable = ['name', 'slug', 'code', 'is_active', 'source_name', 'source_url', 'last_verified_at', 'verification_status'];

    protected $casts = ['last_verified_at' => 'datetime', 'is_active' => 'boolean'];

    public function areas()
    {
        return $this->hasMany(IndustrialArea::class, 'state_id');
    }
}
