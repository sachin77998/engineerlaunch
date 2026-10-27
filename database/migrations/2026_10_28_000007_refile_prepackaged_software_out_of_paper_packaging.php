<?php

use App\Models\Company;
use App\Models\CompanyCategory;
use App\Models\UsListedCompany;
use App\Services\Discovery\CompanyClassifier;
use App\Services\Insights\UsMarketData;
use App\Services\SectorCatalogImporter;
use App\Services\SectorDirectory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

// The classifier's "packag" word filed NASDAQ's "Computer Software: Prepackaged Software", "Package Goods/Cosmetics" and
// "Packaged Foods" under Paper, Pulp & Packaging (Microsoft, Oracle, Adobe, P&G...). With the rule narrowed to "packaging",
// those companies are re-classified and moved. Companies the curated catalogue lists there, and generic fallback results, stay.
return new class extends Migration
{
    public function up(): void
    {
        $paper = CompanyCategory::where('name', 'Paper, Pulp & Packaging')->first();
        if (!$paper) return;
        $catalog = app(SectorCatalogImporter::class);
        $classifier = app(CompanyClassifier::class);
        $us = app(UsMarketData::class);
        // Only companies the curated catalogue itself lists under paper and packaging stay put.
        $curated = collect($catalog->sectors())->flatMap(fn ($s) => collect($s['subsectors'])->filter(fn ($sub) => ($sub['name'] ?? '') === 'Paper, Pulp & Packaging')
            ->flatMap(fn ($sub) => array_column($sub['companies'], 'name')))->map(fn ($n) => Str::lower($n))->flip();
        $parentId = $paper->parent_id;
        $siblings = CompanyCategory::where('parent_id', $parentId)->where('id', '!=', $paper->id)->pluck('id');

        foreach (Company::whereHas('categories', fn ($q) => $q->where('company_categories.id', $paper->id))->get() as $company) {
            if ($curated->has(Str::lower($company->name))) continue;
            $listing = UsListedCompany::where('company_id', $company->id)->first();
            $match = $listing ? $us->classify($listing) : $classifier->localise($classifier->classify($company->name, (string) $company->products), $company->country, $company->name);
            if ($match[1] === 'Paper, Pulp & Packaging' || $classifier->isFallback($match)) continue;
            $detach = [$paper->id];
            if ($parentId && $match[0] !== 'Cement, Building Materials & Polymers' && !$company->categories()->whereIn('company_categories.id', $siblings)->exists()) $detach[] = $parentId;
            $company->categories()->detach($detach);
            $catalog->attach($company, ...$match);
            if (in_array($company->industry, ['Cement, Building Materials & Polymers', null, ''], true) || $company->sector === 'Paper, Pulp & Packaging') {
                $company->forceFill(['industry' => $match[0], 'sector' => $match[1]])->save();
            }
        }
        SectorDirectory::flush();
    }

    public function down(): void
    {
        // Data correction; not reversed.
    }
};
