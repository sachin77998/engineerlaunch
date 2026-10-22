<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HiringScreeningRule extends Model
{
    protected $fillable = ['job_id', 'min_experience', 'max_experience', 'max_notice_days', 'max_expected_salary', 'min_skill_match', 'locations', 'require_verified_phone', 'auto_shortlist_score', 'auto_reject', 'is_active'];
    protected $casts = ['locations' => 'array', 'require_verified_phone' => 'boolean', 'auto_reject' => 'boolean', 'is_active' => 'boolean', 'min_experience' => 'float', 'max_experience' => 'float', 'max_expected_salary' => 'float'];
}
