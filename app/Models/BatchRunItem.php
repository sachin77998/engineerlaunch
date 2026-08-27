<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BatchRunItem extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'context' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function batchRun()
    {
        return $this->belongsTo(BatchRun::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
