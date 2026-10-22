<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HiringCostSetting extends Model
{
    protected $fillable = ['user_id', 'recruiters', 'monthly_cost_per_recruiter', 'monthly_tools_spend', 'monthly_job_board_spend', 'agency_fee_per_hire', 'minutes_per_manual_screen'];
    protected $casts = ['monthly_cost_per_recruiter' => 'float', 'monthly_tools_spend' => 'float', 'monthly_job_board_spend' => 'float', 'agency_fee_per_hire' => 'float'];
}
