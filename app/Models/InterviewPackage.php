<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InterviewPackage extends Model {
 protected $fillable=['company_id','role_id','experience_id','location','experience_years','base_salary','bonus','stock_value','total_compensation','currency','source_type','notes','is_verified'];
 protected $casts=['is_verified'=>'boolean','experience_years'=>'decimal:1','base_salary'=>'decimal:2','bonus'=>'decimal:2','stock_value'=>'decimal:2','total_compensation'=>'decimal:2'];
 public function company(){return $this->belongsTo(InterviewCompany::class,'company_id');} public function role(){return $this->belongsTo(InterviewRole::class,'role_id');} public function experience(){return $this->belongsTo(InterviewExperience::class,'experience_id');}
}
