<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class InstitutionLookupService
{
    private const DATA_URL = 'https://raw.githubusercontent.com/Hipo/university-domains-list/master/world_universities_and_domains.json';

    public function search(string $query, string $country = 'India', int $limit = 30): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        $fallback = collect(config('resume.institutions', []))
            ->merge(config('resume.degrees', []));

        try {
            $records = Cache::remember('institution-directory:v1', now()->addDay(), function (): array {
                $response = Http::acceptJson()->timeout(8)->retry(2, 150)->get(self::DATA_URL);
                $response->throw();

                return $response->json() ?: [];
            });

            $remote = collect($records)
                ->filter(fn (array $record) => strcasecmp((string) ($record['country'] ?? ''), $country) === 0)
                ->pluck('name');
        } catch (\Throwable $exception) {
            report($exception);
            $remote = new Collection();
        }

        return $remote->merge($fallback)
            ->filter(fn ($name) => is_string($name) && str_contains(mb_strtolower($name), mb_strtolower($query)))
            ->unique(fn ($name) => mb_strtolower($name))
            ->sort()
            ->take($limit)
            ->values()
            ->all();
    }
}
