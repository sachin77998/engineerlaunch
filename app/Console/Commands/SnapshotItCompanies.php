<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Builds resources/data/it-software-companies.json: every IT/software company on Wikidata (CC0)
 * that has an official website, worldwide. ItCompanyDirectorySeeder loads this file.
 */
class SnapshotItCompanies extends Command
{
    protected $signature = 'companies:snapshot-it {--path= : Output file}';
    protected $description = 'Regenerate the worldwide IT & software company directory from Wikidata';

    // Wikidata industry items (P452) treated as IT & software.
    public const INDUSTRIES = [
        'Q880371' => 'software industry', 'Q11661' => 'information technology', 'Q638608' => 'software development',
        'Q7397' => 'software', 'Q1540863' => 'IT consulting', 'Q1654942' => 'IT services', 'Q483639' => 'cloud computing',
        'Q3510521' => 'cybersecurity', 'Q56611700' => 'internet', 'Q1254596' => 'software as a service',
        'Q484847' => 'e-commerce', 'Q16319025' => 'fintech', 'Q11016' => 'technology',
    ];

    public function handle(): int
    {
        $companies = [];
        foreach (self::INDUSTRIES as $qid => $label) {
            $query = <<<SPARQL
SELECT ?item ?itemLabel ?website ?countryLabel ?hqLabel ?employees WHERE {
  ?item wdt:P452 wd:{$qid}; wdt:P856 ?website.
  FILTER NOT EXISTS { ?item wdt:P576 ?dissolved }
  OPTIONAL { ?item wdt:P17 ?country }
  OPTIONAL { ?item wdt:P159 ?hq }
  OPTIONAL { ?item wdt:P1128 ?employees }
  SERVICE wikibase:label { bd:serviceParam wikibase:language "en". }
} LIMIT 8000
SPARQL;
            $response = Http::withHeaders(['User-Agent' => config('discovery.user_agent'), 'Accept' => 'application/sparql-results+json'])
                ->timeout(120)->retry(3, 10000)->get(config('discovery.wikidata_url'), ['query' => $query]);
            if (!$response->successful()) { $this->warn("{$label}: HTTP {$response->status()}"); continue; }
            $rows = $response->json('results.bindings', []);
            foreach ($rows as $row) {
                $name = trim($row['itemLabel']['value'] ?? '');
                $website = rtrim(trim($row['website']['value'] ?? ''), '/');
                if ($name === '' || preg_match('/^Q\d+$/', $name) || strlen($name) > 150 || strlen($website) > 255 || !preg_match('~^https?://~i', $website)) continue;
                $key = $row['item']['value'];
                $entry = $companies[$key] ?? ['name' => $name, 'website' => $website, 'country' => null, 'hq' => null, 'employees' => null, 'industries' => []];
                $entry['country'] ??= $row['countryLabel']['value'] ?? null;
                $entry['hq'] ??= $row['hqLabel']['value'] ?? null;
                if (!empty($row['employees']['value'])) $entry['employees'] = max((int) $row['employees']['value'], (int) $entry['employees']);
                $entry['industries'][$label] = true;
                $companies[$key] = $entry;
            }
            $this->line(sprintf('%-24s %5d rows', $label, count($rows)));
            sleep(2);
        }
        $list = array_values(array_map(function ($c) {
            $c['industries'] = array_keys($c['industries']);
            if (preg_match('/^Q\d+$/', (string) $c['country'])) $c['country'] = null;
            if (preg_match('/^Q\d+$/', (string) $c['hq'])) $c['hq'] = null;
            return $c;
        }, $companies));
        usort($list, fn ($a, $b) => strcasecmp($a['name'], $b['name']));
        $path = $this->option('path') ?: resource_path('data/it-software-companies.json');
        file_put_contents($path, json_encode(['source' => 'Wikidata (CC0)', 'generated_at' => now()->toDateString(), 'companies' => $list], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->info(count($list) . " IT & software companies written to {$path}");
        return self::SUCCESS;
    }
}
