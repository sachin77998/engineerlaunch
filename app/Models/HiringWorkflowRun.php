<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HiringWorkflowRun extends Model
{
    public const UPDATED_AT = null;
    protected $fillable = ['workflow_id', 'job_application_id', 'trigger', 'status', 'log', 'minutes_saved'];
    protected $casts = ['log' => 'array'];

    public function workflow()
    {
        return $this->belongsTo(HiringWorkflow::class, 'workflow_id');
    }

    public function application()
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }
}
