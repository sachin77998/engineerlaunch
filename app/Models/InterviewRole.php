<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InterviewRole extends Model {
 protected $fillable=['company_id','role_name','experience_level','department','is_active']; protected $casts=['is_active'=>'boolean'];
 public function company(){return $this->belongsTo(InterviewCompany::class,'company_id');}
 public function experiences(){return $this->hasMany(InterviewExperience::class,'role_id');}
}
