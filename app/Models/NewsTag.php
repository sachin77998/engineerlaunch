<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class NewsTag extends Model{protected $guarded=[];public function articles(){return $this->belongsToMany(NewsArticle::class,'news_article_tag');}}
