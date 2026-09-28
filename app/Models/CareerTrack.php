<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CareerTrack extends Model {
    protected $guarded = ['id'];
    public function roles() { return $this->hasMany(CareerRole::class)->orderBy('sort_order'); }
}
