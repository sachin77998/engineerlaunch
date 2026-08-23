<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class JobApplication extends Model
{
    protected $fillable=['job_id','user_id','status','cover_letter','applied_at'];
    protected $casts=['applied_at'=>'datetime'];
    public function candidate(){return $this->belongsTo(User::class,'user_id');}
    public function job(){return $this->belongsTo(Job::class);}
    public function answers(){return $this->hasMany(ApplicationAnswer::class);}
}
