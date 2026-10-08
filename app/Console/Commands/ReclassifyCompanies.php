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
    protected $signature = 'companies:reclassify';
    protected $description = 'Re-run the sector classifier for companies still filed under "Other Industries"';

    public function handle(CompanyClassifier $classifier, SectorCatalogImporter $catalog): int
    {
        [$fallbackSector, $fallbackSub] = CompanyClassifier::FALLBACK;
        $fallbackIds = CompanyCategory::whereIn('slug', [
            'sector-' . Str::slug($fallbackSector), 'sector-' . Str::slug($fallbackSector) . '-' . Str::slug($fallbackSub),
        ])->pluck('id');
        if ($fallbackIds->isEmpty()) { $this->info('Nothing to reclassify.'); return self::SUCCESS; }

        $moved = 0;
        Company::whereHas('categories', fn ($q) => $q->whereIn('company_categories.id', $fallbackIds))
            ->chunkById(500, function ($companies) use ($classifier, $catalog, $fallbackIds, &$moved) {
                foreach ($companies as $company) {
                    $match = $classifier->classify($company->name, (string) $company->products, (string) $company->industry, (string) $company->sector);
                    if ($classifier->isFallback($match)) continue;
                    $company->categories()->detach($fallbackIds);
                    $catalog->attach($company, ...$match);
                    if ($company->industry === CompanyClassifier::FALLBACK[0]) $company->update(['industry' => $match[0], 'sector' => $match[1]]);
                    $moved++;
                }
            });
        SectorDirectory::flush();
        $this->info("Moved {$moved} companies into specific sectors.");
        return self::SUCCESS;
    }
}
