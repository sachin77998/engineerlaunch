<?php

namespace App\Services\Discovery;

use App\Services\SectorCatalogImporter;
use Illuminate\Support\Facades\DB;

/** Persists discovered companies into the sector catalog, with an optional plant in the scanned hub. */
class DiscoveryWriter
{
    public function __construct(private SectorCatalogImporter $catalog, private CompanyClassifier $classifier) {}

    /** @return array{companies:int,created:int,updated:int} */
    public function write(array $companies, ?array $hub = null): array
    {
        $result = ['companies' => 0, 'created' => 0, 'updated' => 0];
        foreach ($companies as $entry) {
            DB::transaction(function () use ($entry, $hub, &$result) {
                [$sector, $subsector] = $this->classifier->classify($entry['name'], implode(' ', $entry['industries'] ?? []), $entry['tags'] ?? '', $entry['products'] ?? '');
                $company = $this->catalog->upsertCompany($entry, $sector, $subsector);
                $this->catalog->attach($company, $sector, $subsector);
                if ($hub) {
                    $this->catalog->upsertFacility($company, $entry, [
                        'state' => $hub['state'] ?? null, 'city' => $hub['city'] ?? null, 'area' => $hub['name'],
                        'type' => ($hub['type'] ?? null) === 'tech_park' ? 'Office' : 'Plant / Unit',
                        'source_url' => $entry['source_url'] ?? null, 'verified_on' => now()->toDateString(),
                    ], $sector);
                }
                $result[$company->wasRecentlyCreated ? 'created' : 'updated']++;
                $result['companies']++;
            });
        }
        return $result;
    }
}
