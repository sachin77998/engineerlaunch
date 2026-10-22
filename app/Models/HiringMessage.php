<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HiringMessage extends Model
{
    protected $fillable = ['job_application_id', 'sent_by', 'to_email', 'subject', 'body', 'status', 'error', 'sent_at'];
    protected $casts = ['sent_at' => 'datetime'];
    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
    public function application()
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }
}
