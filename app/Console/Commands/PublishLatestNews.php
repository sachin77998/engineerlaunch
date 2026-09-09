<?php
namespace App\Console\Commands;use App\Models\NewsArticle;use Illuminate\Console\Command;
class PublishLatestNews extends Command{protected $signature='news:publish {--limit=50}';protected $description='Publish relevant processed news';public function handle():int{$n=NewsArticle::where('processing_status','processed')->where('relevance_score','>=',config('news.publish_threshold'))->limit((int)$this->option('limit'))->update(['is_published'=>true,'processing_status'=>'published','published_at'=>now()]);$this->info("Published: $n");return self::SUCCESS;}}
