<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ApplicationAnswer extends Model { public $timestamps=false; protected $fillable=['job_application_id','question_id','answer']; }
