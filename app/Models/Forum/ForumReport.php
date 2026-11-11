<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ForumReport extends Model
{
    protected $guarded = [];

    protected $casts = ['resolved_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }

    public function target()
    {
        return $this->target_type === 'question' ? ForumQuestion::find($this->target_id) : ForumAnswer::with('question')->find($this->target_id);
    }
}
