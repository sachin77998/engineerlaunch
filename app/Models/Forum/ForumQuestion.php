<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ForumQuestion extends Model
{
    protected $guarded = [];

    protected $casts = ['last_answered_at' => 'datetime', 'is_hidden' => 'boolean'];

    public function user() { return $this->belongsTo(User::class); }
    public function answers() { return $this->hasMany(ForumAnswer::class); }

    /** The answer shown in lists: the best one, else the most upvoted, else the latest. */
    public function topAnswer()
    {
        return $this->hasOne(ForumAnswer::class)->where('is_hidden', false)->orderByDesc('is_best')->orderByDesc('upvotes')->latest();
    }

    public function scopeVisible($q) { return $q->where('is_hidden', false); }

    public function url(): string
    {
        return route('forum.question', [$this->category, $this->id . ($this->slug ? '-' . $this->slug : '')]);
    }

    public function categoryLabel(): string
    {
        return config('forum.categories')[$this->category][0] ?? $this->category;
    }

    /** Latin titles get a readable slug; others (Hindi, Marathi…) fall back to the id only. */
    public static function slugFor(string $title): ?string
    {
        if (preg_match('/[^\p{Latin}\p{N}\p{P}\p{S}\s]/u', $title)) return null; // transliterated Devanagari etc. reads as gibberish
        return Str::limit(Str::slug($title), 120, '') ?: null;
    }

    public function recountAnswers(): void
    {
        $visible = $this->answers()->where('is_hidden', false);
        $this->update(['answers_count' => (clone $visible)->count(), 'last_answered_at' => (clone $visible)->max('created_at')]);
    }
}
