<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class EmployerProfile extends Model{protected $fillable=['user_id','company_id','designation','phone','verification_status'];public function company(){return $this->belongsTo(Company::class);}}
