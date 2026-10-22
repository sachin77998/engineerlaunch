<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateVerification extends Model
{
    protected $fillable = ['job_application_id', 'candidate_id', 'requested_by', 'type', 'reference', 'provider', 'status', 'message', 'payload', 'consent_at'];
    protected $casts = ['payload' => 'array', 'consent_at' => 'datetime'];
}
