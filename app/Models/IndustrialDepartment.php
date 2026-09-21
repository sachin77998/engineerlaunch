<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustrialDepartment extends Model
{
    use \App\Models\Concerns\HasIndustrialSource;
    protected $fillable = ['name', 'slug', 'category', 'is_active', 'source_name', 'source_url', 'last_verified_at', 'verification_status'];
    protected $casts = ['last_verified_at' => 'datetime', 'is_active' => 'boolean'];
    public function jobs(){return $this->hasMany(IndustrialJob::class, 'department_id');}
    public function roles(){return $this->hasMany(IndustrialJobRole::class, 'department_id');}
}
