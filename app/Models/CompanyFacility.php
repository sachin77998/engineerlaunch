<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CompanyFacility extends Model {
    protected $guarded = ['id'];
    protected $casts = ['verified_on'=>'date','employee_min'=>'integer','employee_max'=>'integer'];
    public function company() { return $this->belongsTo(Company::class); }
}
