<?php

namespace App\Services\News;

use App\Models\NewsSource;
use Carbon\Carbon;
use Illuminate\Support\Str;

class NewsNormalizerService
{
    public function normalize(array $item, NewsSource $source): ?array
    {
        $title = trim(strip_tags((string)($item['title'] ?? '')));
        $url = trim((string)($item['url'] ?? ''));
        if ($title === '' || !filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) return null;
        $published = $item['published_at'] ?? null;
        try {
            $published = $published ? Carbon::parse($published)->toIso8601String() : null;
        } catch (\Throwable) {
            $published = null;
        }
        return ['title' => $title, 'source' => $source->name, 'source_id' => $source->id, 'url' => $url, 'description' => Str::limit(trim(strip_tags((string)($item['description'] ?? ''))), 2000, ''), 'published_at' => $published, 'image' => filter_var($item['image'] ?? null, FILTER_VALIDATE_URL) ?: null, 'author' => Str::limit(trim(strip_tags((string)($item['author'] ?? ''))), 200, ''), 'source_type' => $source->source_type, 'priority' => (int)$source->priority];
    }
}
