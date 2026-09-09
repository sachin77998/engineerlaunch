<?php
namespace App\Console\Commands;use App\Models\NewsArticle;use App\Services\News\NewsProcessingService;use Illuminate\Console\Command;
class ProcessLatestNews extends Command{protected $signature='news:process {--limit=100}';protected $description='Categorize and enrich fetched news';public function handle(NewsProcessingService $s):int{$n=0;NewsArticle::where('processing_status','fetched')->limit((int)$this->option('limit'))->get()->each(function($a)use($s,&$n){$s->process($a);$n++;});$this->info("Processed: $n");return self::SUCCESS;}}
