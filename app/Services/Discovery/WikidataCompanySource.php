<?php

namespace App\Services\Discovery;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Companies with an official website from Wikidata (CC0), one country page at a time. */
class WikidataCompanySource
{
    // business, company, enterprise, public company, holding company, private company, multinational,
    // state-owned enterprise (PSUs), bank, cooperative (dairy, sugar, banks) and manufacturer.
    // One type per request keeps each query under the public endpoint's 60 s limit.
    public const TYPES = ['Q4830453', 'Q783794', 'Q6881511', 'Q891723', 'Q219577', 'Q1589009', 'Q161726', 'Q270791', 'Q22687', 'Q4539', 'Q13235160'];

    // Research modes that need no list of names: every company of the country tagged with any industry, every
    // subsidiary whose parent organisation is from the country (Tata, Reliance, Adani, Aditya Birla… found by data),
    // and every brand owned or made by such a company. Websites are optional in these modes.
    public const MODES = ['industry', 'subsidiary', 'brand'];

    public function fetch(string $countryQid, string $countryName, int $offset, int $limit = 3000, string $type = 'Q783794'): array
    {
        $select = 'SELECT ?item ?itemLabel ?website ?industryLabel ?hqLabel ?employees ?parentLabel WHERE {';
        $common = <<<SPARQL
  FILTER NOT EXISTS { ?item wdt:P576 ?dissolved }
  FILTER NOT EXISTS { ?item wdt:P31 wd:Q66619497 }
  OPTIONAL { ?item wdt:P856 ?website }
  OPTIONAL { ?item wdt:P452 ?industry }
  OPTIONAL { ?item wdt:P159 ?hq }
  OPTIONAL { ?item wdt:P1128 ?employees }
  SERVICE wikibase:label { bd:serviceParam wikibase:language "en". }
} LIMIT {$limit} OFFSET {$offset}
SPARQL;
        $match = match ($type) {
            'industry' => "?item wdt:P17 wd:{$countryQid}; wdt:P452 ?anyIndustry.",
            // Only businesses: parent organisations also have museums, party units and institutes as children.
            'subsidiary' => "?item wdt:P749 ?parent. ?parent wdt:P17 wd:{$countryQid}. FILTER(EXISTS { ?item wdt:P452 ?anyIndustry } || EXISTS { ?item wdt:P31 ?itemType. VALUES ?itemType { wd:" . implode(' wd:', self::TYPES) . " } })",
            'brand' => "?item wdt:P31/wdt:P279* wd:Q431289; wdt:P127|wdt:P749|wdt:P176 ?parent. ?parent wdt:P17 wd:{$countryQid}.",
            default => "?item wdt:P31 wd:{$type}; wdt:P17 wd:{$countryQid}; wdt:P856 ?website.",
        };
        $response = Http::withHeaders(['User-Agent' => config('discovery.user_agent'), 'Accept' => 'application/sparql-results+json'])
            ->timeout(120)->retry(2, 5000)->get(config('discovery.wikidata_url'), ['query' => $select . "\n  " . $match . "\n" . $common]);
        if (!$response->successful()) throw new RuntimeException('Wikidata returned HTTP ' . $response->status());

        $companies = [];
        foreach ($response->json('results.bindings', []) as $row) {
            $name = trim($row['itemLabel']['value'] ?? '');
            $website = trim($row['website']['value'] ?? '');
            // Items without an English label come back as their Q-id.
            if ($name === '' || preg_match('/^Q\d+$/', $name)) continue;
            if (strlen($website) > 255 || !preg_match('~^https?://~i', $website)) $website = '';
            $key = $row['item']['value'];
            $company = $companies[$key] ?? ['name' => $name, 'website' => $website !== '' ? rtrim($website, '/') : null, 'country' => $countryName, 'industries' => []];
            if ($website !== '' && empty($company['website'])) $company['website'] = rtrim($website, '/');
            if (!empty($row['parentLabel']['value']) && !preg_match('/^Q\d+$/', $row['parentLabel']['value'])) $company['parent'] = $row['parentLabel']['value'];
            if (!empty($row['industryLabel']['value'])) $company['industries'][] = $row['industryLabel']['value'];
            if (!empty($row['hqLabel']['value'])) $company['hq'] = $row['hqLabel']['value'];
            if (!empty($row['employees']['value'])) $company['employees'] = max((int) $row['employees']['value'], $company['employees'] ?? 0);
            $company['source_url'] = $key;
            $companies[$key] = $company;
        }
        return array_values(array_map(function ($company) {
            $company['industries'] = array_values(array_unique($company['industries']));
            $company['products'] = $company['industries'] ? implode(', ', array_slice($company['industries'], 0, 4)) : null;
            // Kept out of classification: a Tata hotel must not be filed under steel because of "Tata Steel".
            if (isset($company['parent'])) $company['description'] = 'Part of ' . $company['parent'] . '.';
            return $company;
        }, $companies));
    }
}
