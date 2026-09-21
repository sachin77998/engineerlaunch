<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewExperience extends Model
{
    protected $fillable = ['company_id', 'role_id', 'user_id', 'candidate_name', 'experience_level', 'location', 'interview_date', 'application_source', 'result', 'difficulty', 'total_rounds', 'duration_days', 'overall_experience', 'preparation_advice', 'moderation_status', 'moderated_by', 'moderated_at', 'is_verified', 'is_published'];
    protected $casts = ['interview_date' => 'date', 'moderated_at' => 'datetime', 'is_verified' => 'boolean', 'is_published' => 'boolean'];
    public function company()
    {
        return $this->belongsTo(InterviewCompany::class, 'company_id');
    }
    public function role()
    {
        return $this->belongsTo(InterviewRole::class, 'role_id');
    }
    public function rounds()
    {
        return $this->hasMany(InterviewRound::class, 'experience_id')->orderBy('round_number');
    }
    public function package()
    {
        return $this->hasOne(InterviewPackage::class, 'experience_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }
}
