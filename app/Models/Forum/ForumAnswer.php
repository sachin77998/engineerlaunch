<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ForumAnswer extends Model
{
    protected $guarded = [];

    protected $casts = ['is_best' => 'boolean', 'is_hidden' => 'boolean'];

    public function user() { return $this->belongsTo(User::class); }
    public function question() { return $this->belongsTo(ForumQuestion::class, 'forum_question_id'); }
    public function votes() { return $this->hasMany(ForumAnswerVote::class); }
}
