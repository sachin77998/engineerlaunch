<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CareerRole extends Model {
    protected $guarded = ['id'];
    protected $casts = ['skills'=>'array','next_roles'=>'array'];
    public function track() { return $this->belongsTo(CareerTrack::class,'career_track_id'); }
}
