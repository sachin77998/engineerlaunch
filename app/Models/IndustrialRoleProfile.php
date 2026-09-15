<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class IndustrialRoleProfile extends Model {
 protected $guarded=['id'];
 protected $casts=['skills'=>'array','qualifications'=>'array'];
 public function role(){return $this->belongsTo(IndustrialJobRole::class,'job_role_id');}
}
