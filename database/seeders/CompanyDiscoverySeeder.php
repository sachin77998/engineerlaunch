<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CompanyDiscoverySeeder extends Seeder
{
    public function run(): void
    {
        $collections = [
            ['MNCs', 'MNC'],
            ['Product Companies', 'PRD'],
            ['Banking & Finance', 'BFSI'],
            ['FMCG & Retail', 'RTL'],
            ['Startups', 'START'],
            ['Education Technology', 'EDU'],
            ['Healthcare', 'HLTH'],
            ['Unicorns', 'UNI'],
        ];

        $types = [
            ['Corporate', 'CORP'],
            ['Foreign MNC', 'FMNC'],
            ['Startup', 'START'],
            ['Indian MNC', 'IMNC'],
        ];

        CompanyCategory::whereIn('taxonomy', ['collection', 'company_type'])
            ->update(['is_active' => false]);

        foreach (['collection' => $collections, 'company_type' => $types] as $taxonomy => $items) {
            foreach ($items as $position => [$name, $symbol]) {
                CompanyCategory::updateOrCreate(
                    ['taxonomy' => $taxonomy, 'slug' => Str::slug($name)],
                    ['name' => $name, 'symbol' => $symbol, 'sort_order' => $position + 1, 'is_active' => true]
                );
            }
        }

        $categories = CompanyCategory::whereIn('taxonomy', ['collection', 'company_type'])
            ->where('is_active', true)
            ->get()
            ->keyBy(fn (CompanyCategory $category) => $category->taxonomy.':'.$category->name);

        Company::query()
            ->select(['id', 'name', 'industry', 'sector', 'country', 'organization_type', 'business_type', 'company_type'])
            ->chunkById(250, function ($companies) use ($categories): void {
                foreach ($companies as $company) {
                    $text = Str::lower(implode(' ', array_filter([
                        $company->name,
                        $company->industry,
                        $company->sector,
                        $company->organization_type,
                        $company->business_type,
                        $company->company_type,
                    ])));

                    $labels = $this->collectionLabels($text);
                    $labels[] = $this->companyType($text, $company->country);

                    $ids = collect($labels)->map(function (string $label) use ($categories) {
                        $taxonomy = in_array($label, ['Corporate', 'Foreign MNC', 'Startup', 'Indian MNC'], true)
                            ? 'company_type'
                            : 'collection';

                        return $categories->get($taxonomy.':'.$label)?->id;
                    })->filter()->unique()->all();

                    $company->categories()->syncWithoutDetaching($ids);
                }
            });

        Cache::forget('company-discovery:facets:v3');
        Cache::forget('company-discovery:countries:v2');
        Cache::forget('company-discovery:locations:v3');
    }

    private function collectionLabels(string $text): array
    {
        $labels = [];
        $rules = [
            'MNCs' => ['mnc', 'multinational', 'global corporation'],
            'Product Companies' => ['software product', 'saas', 'product company', 'cloud platform', 'internet'],
            'Banking & Finance' => ['bank', 'finance', 'financial', 'fintech', 'insurance', 'nbfc', 'brokerage', 'payments'],
            'FMCG & Retail' => ['fmcg', 'retail', 'consumer goods', 'ecommerce', 'e-commerce', 'food', 'beverage'],
            'Startups' => ['startup', 'start-up'],
            'Education Technology' => ['edtech', 'e-learning', 'education', 'training'],
            'Healthcare' => ['health', 'medical', 'hospital', 'pharma', 'biotech', 'clinical'],
            'Unicorns' => ['unicorn'],
        ];

        foreach ($rules as $label => $needles) {
            if (Str::contains($text, $needles)) {
                $labels[] = $label;
            }
        }

        return $labels;
    }

    private function companyType(string $text, ?string $country): string
    {
        if (Str::contains($text, ['startup', 'start-up'])) {
            return 'Startup';
        }

        if (Str::contains($text, ['indian mnc'])) {
            return 'Indian MNC';
        }

        if (Str::contains($text, ['foreign mnc', 'multinational', ' mnc'])) {
            return Str::lower((string) $country) === 'india' ? 'Indian MNC' : 'Foreign MNC';
        }

        return 'Corporate';
    }
}
