<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class SalaryInsight extends Model{protected $guarded=[];protected $casts=['value_skills'=>'array'];public function article(){return $this->belongsTo(NewsArticle::class,'news_article_id');}}
