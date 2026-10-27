<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    // Recruiter pipeline stages and the candidate-facing status each one shows.
    public const STAGES = ['new' => 'New', 'screening' => 'Screening', 'shortlisted' => 'Shortlisted', 'interview' => 'Interview', 'offered' => 'Offered', 'hired' => 'Hired', 'rejected' => 'Rejected', 'withdrawn' => 'Withdrawn'];
    public const STAGE_STATUS = ['new' => 'viewed_by_recruiter', 'screening' => 'in_progress', 'shortlisted' => 'in_progress', 'interview' => 'in_progress', 'offered' => 'in_progress', 'hired' => 'joined', 'rejected' => 'rejected', 'withdrawn' => 'withdrawn'];
    protected $fillable = ['job_id', 'user_id', 'status', 'stage', 'stage_changed_at', 'rating', 'tags', 'rejection_reason', 'screening_result', 'screened_at', 'cover_letter', 'applied_at', 'application_source', 'match_score', 'matched_skills', 'resume_id'];
    protected $casts = ['applied_at' => 'datetime', 'stage_changed_at' => 'datetime', 'screened_at' => 'datetime', 'match_score' => 'decimal:2', 'matched_skills' => 'array', 'tags' => 'array', 'screening_result' => 'array'];
    protected static function booted(): void
    {
        // Every new application is scored and screened against the job's rules before a recruiter opens it.
        static::created(function (JobApplication $application) {
            rescue(fn () => app(\App\Services\Hiring\ApplicationScreening::class)->screen($application), null);
            rescue(fn () => app(\App\Services\Hiring\Workflows::class)->fire('application.new', $application), null);
        });
    }
    public function candidate()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function job()
    {
        return $this->belongsTo(Job::class);
    }
    public function answers()
    {
        return $this->hasMany(ApplicationAnswer::class);
    }
    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }
    public function notes()
    {
        return $this->hasMany(HiringNote::class)->latest();
    }
    public function interviews()
    {
        return $this->hasMany(HiringInterview::class)->orderBy('scheduled_at');
    }
    public function verifications()
    {
        return $this->hasMany(CandidateVerification::class)->latest();
    }
    public function messages()
    {
        return $this->hasMany(HiringMessage::class)->latest();
    }
    public function offers()
    {
        return $this->hasMany(HiringOffer::class)->latest();
    }
}
