<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class JobLocation extends Model { protected $fillable = ['job_id','country','state','city']; }
