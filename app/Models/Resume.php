<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class Resume extends Model{protected $fillable=['candidate_profile_id','disk','path','original_name','is_primary','mime_type','file_size','parsing_status','parsed_data','parse_error','parsed_at'];protected $casts=['is_primary'=>'boolean','parsed_data'=>'array','parsed_at'=>'datetime'];public function profile(){return $this->belongsTo(CandidateProfile::class,'candidate_profile_id');}}
