<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyCategory;
use App\Models\CompanyFacility;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Imports resources/data/sector-catalog/*.json into the sector -> subsector -> company hierarchy.
 * Existing company data (website, careers URL, ATS adapter) is never overwritten; brands are merged.
 */
class SectorCatalogImporter
{
    public const TAXONOMY = 'sector';
    public const CATALOG_DATE = '2026-10-08';

    public function sectors(): array
    {
        $files = glob(resource_path('data/sector-catalog/*.json')) ?: [];
        sort($files);
        // A sector may be extended by later files; merge by name so order and roles come from its first definition.
        $sectors = [];
        foreach ($files as $file) {
            $payload = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
            foreach ($payload['sectors'] ?? [] as $sector) {
                $existing = &$sectors[$sector['name']];
                if ($existing === null) { $existing = $sector + ['subsectors' => []]; $existing['subsectors'] = []; }
                $existing['roles'] = ($existing['roles'] ?? []) + ($sector['roles'] ?? []);
                $existing['symbol'] ??= $sector['symbol'] ?? null;
                foreach ($sector['subsectors'] ?? [] as $subsector) {
                    $key = $subsector['name'];
                    $existing['subsectors'][$key]['name'] = $key;
                    $existing['subsectors'][$key]['companies'] = array_merge($existing['subsectors'][$key]['companies'] ?? [], $subsector['companies'] ?? []);
                }
                unset($existing);
            }
        }
        return array_values(array_map(fn ($sector) => ['subsectors' => array_values($sector['subsectors'])] + $sector, $sectors));
    }

    public function importAll(): array
    {
        $totals = ['sectors' => 0, 'companies' => 0, 'created' => 0, 'updated' => 0, 'facilities' => 0];
        foreach ($this->sectors() as $index => $sector) {
            foreach ($this->importSector($sector, $index) as $key => $count) $totals[$key] += $count;
            $totals['sectors']++;
        }
        return $totals;
    }

    public function importSector(array $sector, int $order): array
    {
        $result = ['companies' => 0, 'created' => 0, 'updated' => 0, 'facilities' => 0];
        DB::transaction(function () use ($sector, $order, &$result) {
            $sectorSlug = Str::slug($sector['name']);
            $parent = CompanyCategory::updateOrCreate(['slug' => Str::limit('sector-' . $sectorSlug, 140, '')], [
                'parent_id' => null, 'name' => $sector['name'], 'taxonomy' => self::TAXONOMY,
                'symbol' => $sector['symbol'] ?? '•', 'sort_order' => $order * 100,
                'roles' => $sector['roles'] ?? null, 'is_active' => true,
            ]);
            foreach ($sector['subsectors'] ?? [] as $position => $subsector) {
                $child = CompanyCategory::updateOrCreate(['slug' => Str::limit('sector-' . $sectorSlug . '-' . Str::slug($subsector['name']), 140, '')], [
                    'parent_id' => $parent->id, 'name' => $subsector['name'], 'taxonomy' => self::TAXONOMY,
                    'symbol' => '•', 'sort_order' => $order * 100 + $position + 1, 'is_active' => true,
                ]);
                foreach ($subsector['companies'] ?? [] as $entry) {
                    if (blank($entry['name'] ?? null)) continue;
                    $company = $this->upsertCompany($entry, $sector['name'], $subsector['name']);
                    $result[$company->wasRecentlyCreated ? 'created' : 'updated']++;
                    $result['companies']++;
                    $company->categories()->syncWithoutDetaching([$parent->id, $child->id]);
                    foreach ($entry['plants'] ?? [] as $plant) {
                        $this->upsertFacility($company, $entry, $plant, $sector['name']);
                        $result['facilities']++;
                    }
                }
            }
        });
        return $result;
    }

    public function upsertCompany(array $entry, string $sector, string $subsector): Company
    {
        $slug = Str::slug($entry['name']);
        $company = Company::where('slug', $slug)->orWhere('name', $entry['name'])->first()
            ?? new Company(['name' => $entry['name'], 'slug' => $slug]);
        $website = $entry['website'] ?? null;
        $company->forceFill([
            'website' => $company->website ?: $website,
            'careers_url' => $company->careers_url ?: ($entry['careers_url'] ?? null),
            'country' => $company->country ?: ($entry['country'] ?? null),
            'headquarters' => $entry['hq'] ?? $company->headquarters,
            'industry' => $company->industry ?: $sector,
            'sector' => $company->sector ?: $subsector,
            'products' => $company->products ?: ($entry['products'] ?? null),
            'brands' => array_values(array_unique(array_merge($company->brands ?? [], $entry['brands'] ?? []))) ?: null,
            'employee_count' => $company->employee_count ?: ($entry['employees'] ?? null),
            'is_active' => true,
        ]);
        // Companies with an official website join the daily opening batch through bounded career-page discovery.
        if (filled($company->website) && blank($company->ats_provider)) {
            $company->ats_provider = 'official_discovery';
            $company->sync_enabled = true;
        }
        $company->save();
        return $company;
    }

    public function upsertFacility(Company $company, array $entry, array $plant, string $sector): void
    {
        $label = $plant['area'] ?? $plant['city'] ?? 'Plant';
        $slug = Str::limit(Str::slug($company->slug . ' ' . ($plant['city'] ?? '') . ' ' . $label), 180, '');
        CompanyFacility::updateOrCreate(['slug' => $slug], [
            'company_id' => $company->id,
            'name' => $company->name . ' - ' . $label,
            'facility_type' => $plant['type'] ?? 'Plant / Unit',
            'country' => $plant['country'] ?? 'India',
            'state' => $plant['state'] ?? null,
            'city' => $plant['city'] ?? null,
            'industrial_area' => $plant['area'] ?? null,
            'industry' => $sector,
            'products' => $plant['products'] ?? ($entry['products'] ?? null),
            'employee_min' => $plant['employees_min'] ?? null,
            'employee_max' => $plant['employees_max'] ?? null,
            'source_url' => $plant['source_url'] ?? $entry['website'] ?? ('https://www.google.com/search?q=' . rawurlencode($company->name . ' ' . $label)),
            'verified_on' => $plant['verified_on'] ?? self::CATALOG_DATE,
        ]);
    }

    /** Files a company under a sector/subsector, creating either category when discovery finds a new one. */
    public function attach(Company $company, string $sector, string $subsector): void
    {
        $sectorSlug = Str::slug($sector);
        $parent = CompanyCategory::firstOrCreate(['slug' => Str::limit('sector-' . $sectorSlug, 140, '')], [
            'name' => $sector, 'taxonomy' => self::TAXONOMY, 'symbol' => strtoupper(substr($sectorSlug, 0, 2)), 'sort_order' => 9000, 'is_active' => true,
        ]);
        $child = CompanyCategory::firstOrCreate(['slug' => Str::limit('sector-' . $sectorSlug . '-' . Str::slug($subsector), 140, '')], [
            'parent_id' => $parent->id, 'name' => $subsector, 'taxonomy' => self::TAXONOMY, 'symbol' => '•', 'sort_order' => 9000, 'is_active' => true,
        ]);
        $company->categories()->syncWithoutDetaching([$parent->id, $child->id]);
    }
}
