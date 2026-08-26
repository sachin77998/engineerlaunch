<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class EmployerProfile extends Model{protected $fillable=['user_id','company_id','first_name','last_name','designation','phone_country_code','phone','phone_verified_at','verification_status'];protected $casts=['phone_verified_at'=>'datetime'];public function company(){return $this->belongsTo(Company::class);}}
