<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class NewsFetchLog extends Model{protected $guarded=[];protected $casts=['started_at'=>'datetime','finished_at'=>'datetime'];public function source(){return $this->belongsTo(NewsSource::class,'source_id');}}
