<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class JobScreeningQuestion extends Model { protected $fillable=['job_id','question','type','options','is_required','sort_order']; protected $casts=['options'=>'array','is_required'=>'boolean']; }
