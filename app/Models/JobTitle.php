<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobTitle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category', 'title', 'slug', 'is_active', 'sort_order',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = ['is_active' => 'boolean'];
}
