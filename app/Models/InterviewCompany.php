<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InterviewCompany extends Model {
 protected $fillable=['name','slug','logo','tier','industry','country','short_description','is_featured','is_active','experience_count'];
 protected $casts=['is_featured'=>'boolean','is_active'=>'boolean'];
 public function roles(){return $this->hasMany(InterviewRole::class,'company_id');}
 public function experiences(){return $this->hasMany(InterviewExperience::class,'company_id');}
 public function packages(){return $this->hasMany(InterviewPackage::class,'company_id');}
}
