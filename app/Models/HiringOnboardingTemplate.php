<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A reusable joining checklist: [{kind: document|task, label, help, required}].
class HiringOnboardingTemplate extends Model
{
    protected $fillable = ['owner_id', 'name', 'items'];
    protected $casts = ['items' => 'array'];
}
