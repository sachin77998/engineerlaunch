<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InterviewRound extends Model {
 protected $fillable=['experience_id','round_number','round_name','round_type','duration_minutes','difficulty','description','topics','questions','candidate_experience','is_elimination_round'];
 protected $casts=['topics'=>'array','questions'=>'array','is_elimination_round'=>'boolean'];
 public function experience(){return $this->belongsTo(InterviewExperience::class,'experience_id');}
}
