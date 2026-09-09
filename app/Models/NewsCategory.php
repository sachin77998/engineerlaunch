<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class NewsCategory extends Model{protected $guarded=[];protected $casts=['is_active'=>'boolean'];public function articles(){return $this->hasMany(NewsArticle::class,'category_id');}}
