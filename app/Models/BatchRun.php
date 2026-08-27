<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BatchRun extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'context' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(BatchRunItem::class);
    }
}
