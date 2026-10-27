<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// "When <trigger> happens, run <steps>" for one employer team; optionally limited to one job.
class HiringWorkflow extends Model
{
    protected $fillable = ['owner_id', 'created_by', 'name', 'trigger', 'job_id', 'steps', 'is_active', 'runs_count', 'last_run_at'];
    protected $casts = ['steps' => 'array', 'is_active' => 'boolean', 'last_run_at' => 'datetime'];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function runs()
    {
        return $this->hasMany(HiringWorkflowRun::class, 'workflow_id');
    }
}
