<?php
namespace App\Services\News;use App\Models\NewsSource;
class NewsFetcherService{public function __construct(private RssNewsFetcher $rss,private ApiNewsFetcher $api,private OfficialNewsFetcher $official,private GovernmentNewsFetcher $government){}public function fetch(NewsSource $source):array{return match($source->source_type){'rss','media'=>$this->rss->fetch($source),'api'=>$this->api->fetch($source),'official'=>$this->official->fetch($source),'government','regulator'=>$this->government->fetch($source),default=>[]};}}
