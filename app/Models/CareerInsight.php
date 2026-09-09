<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class CareerInsight extends Model{protected $guarded=[];protected $casts=['recommended_skills'=>'array'];public function article(){return $this->belongsTo(NewsArticle::class,'news_article_id');}}
