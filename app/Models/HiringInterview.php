<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HiringInterview extends Model
{
    public const MODES = ['video' => 'Video call', 'phone' => 'Phone', 'onsite' => 'In person'];
    public const RECOMMENDATIONS = ['strong_yes' => 'Strong hire', 'yes' => 'Hire', 'no' => 'No hire', 'strong_no' => 'Strong no'];
    protected $fillable = ['job_application_id', 'scheduled_by', 'round', 'mode', 'scheduled_at', 'duration_minutes', 'location', 'interviewer_name', 'interviewer_email', 'status', 'rating', 'recommendation', 'feedback'];
    protected $casts = ['scheduled_at' => 'datetime'];
    public function application()
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }
}
