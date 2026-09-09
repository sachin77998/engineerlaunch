<?php

namespace App\Services\News;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class IndustryNewsRssService
{
    public function fetch(string $query): array
    {
        $response = Http::timeout(20)
            ->retry(3, 1000)
            ->withHeaders(['User-Agent' => 'Ascendia Career Intelligence News Bot/1.0'])
            ->get(config('industry_news.endpoint'), array_merge(
                ['q' => $query],
                config('industry_news.locale', [])
            ));

        if (! $response->successful()) {
            return [];
        }

        return $this->parse($response->body());
    }

    private function parse(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        $feed = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $feed || ! isset($feed->channel->item)) {
            return [];
        }

        $articles = [];
        foreach ($feed->channel->item as $item) {
            $title = trim((string) $item->title);
            $url = trim((string) $item->link);
            if ($title === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $description = trim(html_entity_decode(strip_tags((string) $item->description), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $source = trim((string) $item->source);
            try {
                $publishedAt = filled((string) $item->pubDate) ? Carbon::parse((string) $item->pubDate) : now();
            } catch (\Throwable) {
                $publishedAt = now();
            }

            $articles[] = [
                'title' => $title,
                'url' => $url,
                'description' => Str::limit($description, 1000),
                'published_at' => $publishedAt,
                'publisher' => $source ?: null,
            ];
        }

        return $articles;
    }
}
