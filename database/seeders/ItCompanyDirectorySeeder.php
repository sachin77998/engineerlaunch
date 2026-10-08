<?php

namespace Database\Seeders;

use App\Models\CompanyCategory;
use App\Services\SectorCatalogImporter;
use App\Services\SectorDirectory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Loads every IT & software company from resources/data/it-software-companies.json (built from Wikidata by
 * `php artisan companies:snapshot-it`). Bulk inserts, skips companies that already exist, never overwrites them.
 */
class ItCompanyDirectorySeeder extends Seeder
{
    private const SECTOR = 'IT Services & Software';
    private const EUROPE = ['United Kingdom', 'Germany', 'France', 'Spain', 'Netherlands', 'Italy', 'Sweden', 'Switzerland', 'Ireland', 'Poland',
        'Finland', 'Norway', 'Denmark', 'Belgium', 'Austria', 'Portugal', 'Czech Republic', 'Estonia', 'Romania', 'Greece', 'Hungary', 'Luxembourg'];

    public function run(): void
    {
        $path = resource_path('data/it-software-companies.json');
        if (!is_file($path)) { $this->command?->warn('No IT company directory file; run php artisan companies:snapshot-it'); return; }
        $companies = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR)['companies'] ?? [];

        $existingSlugs = array_flip(DB::table('companies')->pluck('slug')->all());
        $existingNames = array_flip(array_map('mb_strtolower', DB::table('companies')->pluck('name')->all()));
        $hasCatalogColumns = Schema::hasColumn('companies', 'headquarters') && Schema::hasColumn('companies', 'products');
        $now = now();
        $rows = [];
        $subsectorBySlug = [];
        foreach ($companies as $entry) {
            $name = trim($entry['name']);
            $slug = Str::limit(Str::slug($name), 240, '');
            if ($slug === '' || isset($existingSlugs[$slug]) || isset($existingNames[mb_strtolower($name)])) continue;
            // Wikidata tags some unions, associations and events with IT industries; they are not employers to list.
            if (preg_match('/(union|association|society|federation|foundation|conference|council|community|ministry)/i', $name)) continue;
            $existingSlugs[$slug] = true;
            $existingNames[mb_strtolower($name)] = true;
            $subsector = $this->subsector($entry);
            $row = [
                'name' => Str::limit($name, 250, ''), 'slug' => $slug, 'website' => $entry['website'],
                'country' => Str::limit($entry['country'] ?: 'Global', 250, ''), 'industry' => self::SECTOR, 'sector' => $subsector,
                'employee_count' => isset($entry['employees']) && $entry['employees'] < 2147483647 ? $entry['employees'] : null,
                'ats_provider' => 'official_discovery',
                // Crawling thousands of sites daily is too heavy for shared hosting; large employers first.
                'sync_enabled' => ($entry['employees'] ?? 0) >= 1000,
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ];
            if ($hasCatalogColumns) {
                $row['headquarters'] = isset($entry['hq']) ? Str::limit($entry['hq'], 115, '') : null;
                $row['products'] = implode(', ', $entry['industries'] ?? []);
            }
            $rows[] = $row;
            $subsectorBySlug[$slug] = $subsector;
        }

        foreach (array_chunk($rows, 500) as $chunk) DB::table('companies')->insertOrIgnore($chunk);

        // File every new company under the IT sector and its subsector.
        $categoryIds = $this->categoryIds(array_unique(array_values($subsectorBySlug)));
        foreach (array_chunk(array_keys($subsectorBySlug), 500) as $slugs) {
            $pivot = [];
            foreach (DB::table('companies')->whereIn('slug', $slugs)->pluck('id', 'slug') as $slug => $id) {
                foreach ([$categoryIds['__sector'], $categoryIds[$subsectorBySlug[$slug]]] as $categoryId) {
                    $pivot[] = ['company_id' => $id, 'company_category_id' => $categoryId, 'created_at' => $now, 'updated_at' => $now];
                }
            }
            if ($pivot) DB::table('company_category_company')->insertOrIgnore($pivot);
        }
        SectorDirectory::flush();
        $this->command?->info(count($rows) . ' IT & software companies added (' . (count($companies) - count($rows)) . ' already listed).');
    }

    private function subsector(array $entry): string
    {
        $industries = implode(' ', $entry['industries'] ?? []);
        if (($entry['country'] ?? null) === 'India') return 'Indian IT Services';
        if (str_contains($industries, 'cybersecurity')) return 'Cybersecurity';
        if (str_contains($industries, 'IT consulting') || str_contains($industries, 'IT services')) {
            return in_array($entry['country'] ?? null, self::EUROPE, true) ? 'European IT Services' : 'Global IT Services & Consulting';
        }
        if (preg_match('/e-commerce|internet|fintech/', $industries)) return 'Product & Internet Companies';
        return 'Software Products & SaaS';
    }

    private function categoryIds(array $subsectors): array
    {
        $sectorSlug = Str::slug(self::SECTOR);
        $parent = CompanyCategory::firstOrCreate(['slug' => 'sector-' . $sectorSlug], [
            'name' => self::SECTOR, 'taxonomy' => SectorCatalogImporter::TAXONOMY, 'symbol' => 'IT', 'sort_order' => 9000, 'is_active' => true,
        ]);
        $ids = ['__sector' => $parent->id];
        foreach ($subsectors as $name) {
            $ids[$name] = CompanyCategory::firstOrCreate(['slug' => Str::limit('sector-' . $sectorSlug . '-' . Str::slug($name), 140, '')], [
                'parent_id' => $parent->id, 'name' => $name, 'taxonomy' => SectorCatalogImporter::TAXONOMY, 'symbol' => '•', 'sort_order' => 9000, 'is_active' => true,
            ])->id;
        }
        return $ids;
    }
}
