<?php

namespace App\Services\Discovery;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Companies with an official website from Wikidata (CC0), one country page at a time. */
class WikidataCompanySource
{
    // business, company, enterprise, public company, holding company, private company, multinational.
    // One type per request keeps each query under the public endpoint's 60 s limit.
    public const TYPES = ['Q4830453', 'Q783794', 'Q6881511', 'Q891723', 'Q219577', 'Q1589009', 'Q161726'];

    public function fetch(string $countryQid, string $countryName, int $offset, int $limit = 3000, string $type = 'Q783794'): array
    {
        $query = <<<SPARQL
SELECT ?item ?itemLabel ?website ?industryLabel ?hqLabel ?employees WHERE {
  ?item wdt:P31 wd:%s; wdt:P17 wd:%s; wdt:P856 ?website.
  FILTER NOT EXISTS { ?item wdt:P576 ?dissolved }
  OPTIONAL { ?item wdt:P452 ?industry }
  OPTIONAL { ?item wdt:P159 ?hq }
  OPTIONAL { ?item wdt:P1128 ?employees }
  SERVICE wikibase:label { bd:serviceParam wikibase:language "en". }
} LIMIT %d OFFSET %d
SPARQL;
        $response = Http::withHeaders(['User-Agent' => config('discovery.user_agent'), 'Accept' => 'application/sparql-results+json'])
            ->timeout(120)->retry(2, 5000)->get(config('discovery.wikidata_url'), ['query' => sprintf($query, $type, $countryQid, $limit, $offset)]);
        if (!$response->successful()) throw new RuntimeException('Wikidata returned HTTP ' . $response->status());

        $companies = [];
        foreach ($response->json('results.bindings', []) as $row) {
            $name = trim($row['itemLabel']['value'] ?? '');
            $website = trim($row['website']['value'] ?? '');
            // Items without an English label come back as their Q-id.
            if ($name === '' || preg_match('/^Q\d+$/', $name) || strlen($website) > 255 || !preg_match('~^https?://~i', $website)) continue;
            $key = $row['item']['value'];
            $company = $companies[$key] ?? ['name' => $name, 'website' => rtrim($website, '/'), 'country' => $countryName, 'industries' => []];
            if (!empty($row['industryLabel']['value'])) $company['industries'][] = $row['industryLabel']['value'];
            if (!empty($row['hqLabel']['value'])) $company['hq'] = $row['hqLabel']['value'];
            if (!empty($row['employees']['value'])) $company['employees'] = max((int) $row['employees']['value'], $company['employees'] ?? 0);
            $company['source_url'] = $key;
            $companies[$key] = $company;
        }
        return array_values(array_map(function ($company) {
            $company['industries'] = array_values(array_unique($company['industries']));
            $company['products'] = $company['industries'] ? implode(', ', array_slice($company['industries'], 0, 4)) : null;
            return $company;
        }, $companies));
    }
}
