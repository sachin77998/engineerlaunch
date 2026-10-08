<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyCategory;
use App\Services\Discovery\CompanyClassifier;
use App\Services\SectorCatalogImporter;
use App\Services\SectorDirectory;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ReclassifyCompanies extends Command
{
    protected $signature = 'companies:reclassify {--all : Re-sort every discovered company, not only "Other Industries"}';
    protected $description = 'Re-run the sector classifier for discovered companies';

    public function handle(CompanyClassifier $classifier, SectorCatalogImporter $catalog): int
    {
        // Curated catalog companies keep the categories chosen in resources/data/sector-catalog.
        $curated = collect($catalog->sectors())->flatMap(fn ($s) => collect($s['subsectors'])->flatMap(fn ($sub) => array_column($sub['companies'], 'name')))
            ->map(fn ($name) => Str::lower($name))->flip();
        $sectorNames = collect($catalog->sectors())->pluck('name')->push(CompanyClassifier::FALLBACK[0])->map(fn ($n) => Str::lower($n))->flip();
        [$fallbackSector, $fallbackSub] = CompanyClassifier::FALLBACK;
        $fallbackIds = CompanyCategory::whereIn('slug', [
            'sector-' . Str::slug($fallbackSector), 'sector-' . Str::slug($fallbackSector) . '-' . Str::slug($fallbackSub),
        ])->pluck('id');

        $query = Company::query()->with('categories:id,taxonomy');
        $this->option('all')
            ? $query->whereHas('categories', fn ($q) => $q->where('taxonomy', SectorCatalogImporter::TAXONOMY))
            : $query->whereHas('categories', fn ($q) => $q->whereIn('company_categories.id', $fallbackIds));

        $moved = 0;
        $query->chunkById(500, function ($companies) use ($classifier, $catalog, $curated, $sectorNames, &$moved) {
            foreach ($companies as $company) {
                if ($curated->has(Str::lower($company->name))) continue;
                // Ignore industry/sector when they are just a previous classification label.
                $industry = $sectorNames->has(Str::lower((string) $company->industry)) ? '' : (string) $company->industry;
                $match = $classifier->classify($company->name, (string) $company->products, $industry);
                $current = $company->categories->where('taxonomy', SectorCatalogImporter::TAXONOMY)->pluck('id');
                $catalog->attach($company, ...$match);
                $target = CompanyCategory::whereIn('slug', [
                    'sector-' . Str::slug($match[0]), 'sector-' . Str::slug($match[0]) . '-' . Str::slug($match[1]),
                ])->pluck('id');
                $stale = $current->diff($target);
                if ($stale->isEmpty()) continue;
                $company->categories()->detach($stale);
                $company->forceFill(['industry' => $match[0], 'sector' => $match[1]])->save();
                $moved++;
            }
        });
        SectorDirectory::flush();
        $this->info("Moved {$moved} companies into corrected sectors.");
        return self::SUCCESS;
    }
}
