<?php

namespace App\Services\Discovery;

use App\Models\Company;
use App\Models\CompanyCategory;
use App\Services\SectorCatalogImporter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Bulk-saves registry companies (GLEIF): new companies are inserted and filed into a sector by name; every company
 * also gets a "registered office" location in its city so industrial-area and city counts include it.
 * Existing companies (matched by name_key) are never overwritten.
 */
class RegistryWriter
{
    private array $categoryIds = [];
    private ?bool $hasHeadquarters = null;

    public function __construct(private CompanyClassifier $classifier) {}

    /** @return array{companies:int,created:int,updated:int} */
    public function write(array $rows, string $source = 'GLEIF'): array
    {
        $rows = collect($rows)->map(fn ($row) => $row + ['key' => Company::nameKey($row['name']), 'slug' => Str::limit(Str::slug($row['name']), 230, '')])
            ->filter(fn ($row) => $row['key'] !== '' && $row['slug'] !== '')->unique('key')->values();
        if ($rows->isEmpty()) return ['companies' => 0, 'created' => 0, 'updated' => 0];

        $existing = DB::table('companies')->whereIn('name_key', $rows->pluck('key'))->pluck('id', 'name_key');
        $takenSlugs = DB::table('companies')->whereIn('slug', $rows->pluck('slug'))->pluck('slug')->flip();
        $now = now();
        $new = [];
        foreach ($rows as $row) {
            if ($existing->has($row['key'])) continue;
            $slug = $takenSlugs->has($row['slug']) ? Str::limit($row['slug'] . '-' . Str::slug($row['city'] ?: 'registered'), 240, '') : $row['slug'];
            $match = $this->classifier->classify($row['name']);
            // Every row must have the same columns for a bulk insert, so empty values stay as null.
            $insert = [
                'name' => $row['name'], 'slug' => $slug, 'name_key' => $row['key'], 'country' => $row['country'] ?: 'Global',
                'industry' => $match[0], 'sector' => $match[1], 'is_active' => true, 'sync_enabled' => false,
                'registry_cin' => preg_match('/^[A-Z0-9]{21}$/', (string) $row['registration']) ? $row['registration'] : null,
                'registered_state' => $row['state'] ? Str::limit($row['state'], 100, '') : null,
                'registry_status' => 'ACTIVE',
                'registry_source_url' => $row['lei'] ? 'https://search.gleif.org/#/record/' . $row['lei'] : null,
                'created_at' => $now, 'updated_at' => $now,
            ];
            if ($this->hasHeadquarters ??= Schema::hasColumn('companies', 'headquarters')) $insert['headquarters'] = $row['city'] ? Str::limit($row['city'], 115, '') : null;
            $new[$row['key']] = ['match' => $match, 'insert' => $insert];
        }
        foreach (array_chunk(array_column($new, 'insert'), 200) as $chunk) DB::table('companies')->insertOrIgnore($chunk);

        $ids = DB::table('companies')->whereIn('name_key', $rows->pluck('key'))->pluck('id', 'name_key');
        $pivot = [];
        $facilities = [];
        foreach ($rows as $row) {
            $id = $ids[$row['key']] ?? null;
            if (!$id) continue;
            if (isset($new[$row['key']])) {
                [$sector, $subsector] = $new[$row['key']]['match'];
                foreach ($this->categories($sector, $subsector) as $categoryId) {
                    $pivot[] = ['company_id' => $id, 'company_category_id' => $categoryId, 'created_at' => $now, 'updated_at' => $now];
                }
            }
            if ($row['city']) {
                $facilities[] = [
                    'company_id' => $id, 'slug' => Str::limit(Str::slug($row['slug'] . ' ' . $row['city'] . ' registered'), 240, ''),
                    'name' => Str::limit($row['name'] . ' - ' . $row['city'], 250, ''), 'facility_type' => 'Registered office',
                    'country' => $row['country'] ?: 'India', 'state' => $row['state'], 'city' => $row['city'], 'industrial_area' => null,
                    'industry' => isset($new[$row['key']]) ? $new[$row['key']]['match'][0] : 'Registered company',
                    'source_url' => $row['lei'] ? 'https://search.gleif.org/#/record/' . $row['lei'] : $source,
                    'verified_on' => $now->toDateString(), 'created_at' => $now, 'updated_at' => $now,
                ];
            }
        }
        foreach (array_chunk($pivot, 500) as $chunk) DB::table('company_category_company')->insertOrIgnore($chunk);
        if (Schema::hasTable('company_facilities')) foreach (array_chunk($facilities, 200) as $chunk) DB::table('company_facilities')->insertOrIgnore($chunk);

        return ['companies' => $rows->count(), 'created' => count($new), 'updated' => $rows->count() - count($new)];
    }

    private function categories(string $sector, string $subsector): array
    {
        $key = $sector . '|' . $subsector;
        if (!isset($this->categoryIds[$key])) {
            $sectorSlug = Str::slug($sector);
            $parent = CompanyCategory::firstOrCreate(['slug' => Str::limit('sector-' . $sectorSlug, 140, '')], [
                'name' => $sector, 'taxonomy' => SectorCatalogImporter::TAXONOMY, 'symbol' => strtoupper(substr($sectorSlug, 0, 2)), 'sort_order' => 9000, 'is_active' => true,
            ]);
            $child = CompanyCategory::firstOrCreate(['slug' => Str::limit('sector-' . $sectorSlug . '-' . Str::slug($subsector), 140, '')], [
                'parent_id' => $parent->id, 'name' => $subsector, 'taxonomy' => SectorCatalogImporter::TAXONOMY, 'symbol' => '•', 'sort_order' => 9000, 'is_active' => true,
            ]);
            $this->categoryIds[$key] = [$parent->id, $child->id];
        }
        return $this->categoryIds[$key];
    }
}
