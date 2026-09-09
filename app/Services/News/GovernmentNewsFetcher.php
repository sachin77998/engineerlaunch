<?php
namespace App\Services\News;use App\Models\NewsSource;
class GovernmentNewsFetcher{public function __construct(private RssNewsFetcher $rss,private ApiNewsFetcher $api){}public function fetch(NewsSource $source):array{return $source->feed_url?$this->rss->fetch($source):($source->api_url?$this->api->fetch($source):[]);}}
