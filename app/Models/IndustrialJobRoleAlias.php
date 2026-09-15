<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustrialJobRoleAlias extends Model
{
    protected $guarded = ['id'];

    public function role()
    {
        return $this->belongsTo(IndustrialJobRole::class, 'job_role_id');
    }
}
