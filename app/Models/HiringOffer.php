<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HiringOffer extends Model
{
    public const STATUSES = ['draft' => 'Draft', 'sent' => 'Sent', 'accepted' => 'Accepted', 'declined' => 'Declined', 'withdrawn' => 'Withdrawn', 'expired' => 'Expired'];
    protected $fillable = ['job_application_id', 'created_by', 'designation', 'annual_ctc', 'currency', 'fixed_pay', 'variable_pay', 'joining_bonus', 'joining_date', 'expires_on', 'work_location', 'probation_months', 'terms', 'signatory_name', 'signatory_title', 'status', 'token_hash', 'pdf_path', 'sent_at', 'responded_at', 'response_note'];
    protected $casts = ['joining_date' => 'date', 'expires_on' => 'date', 'sent_at' => 'datetime', 'responded_at' => 'datetime', 'annual_ctc' => 'float', 'fixed_pay' => 'float', 'variable_pay' => 'float', 'joining_bonus' => 'float'];
    public function application()
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }
    // An offer past its validity date that nobody answered counts as expired.
    public function getEffectiveStatusAttribute(): string
    {
        return $this->status === 'sent' && $this->expires_on && $this->expires_on->endOfDay()->isPast() ? 'expired' : $this->status;
    }
}
