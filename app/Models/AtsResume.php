<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtsResume extends Model
{
    protected $fillable = ['user_id','full_name','headline','email','phone','location','summary','skills','experience','education','links','template','completeness'];
    protected $casts = ['skills'=>'array','experience'=>'array','education'=>'array','links'=>'array','completeness'=>'integer'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function calculateCompleteness(): int
    {
        $checks = [$this->full_name,$this->email,$this->phone,$this->summary,$this->skills,$this->experience,$this->education];
        return (int) round(collect($checks)->filter(fn($value)=>filled($value))->count()/count($checks)*100);
    }
}
