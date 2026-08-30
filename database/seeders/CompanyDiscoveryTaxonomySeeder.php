<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CompanyDiscoveryTaxonomySeeder extends Seeder
{
    private const COLLECTIONS = [
        'Global Companies' => 'GL', 'Indian Enterprises' => 'IN',
        'Product Companies' => 'PR', 'Service Companies' => 'SV',
        'Consulting Companies' => 'CS', 'Startups' => 'ST',
        'Banking & Finance' => 'BF', 'Financial Technology' => 'FT',
        'Hospitality & Travel' => 'HT', 'Healthcare Innovators' => 'HC',
        'Education Technology' => 'ED', 'Internet Businesses' => 'WB',
    ];

    public function run(): void
    {
        foreach (self::COLLECTIONS as $name => $symbol) {
            CompanyCategory::updateOrCreate(
                ['slug' => Str::slug('collection-'.$name)],
                ['name'=>$name,'taxonomy'=>'collection','symbol'=>$symbol,'is_active'=>true]
            );
        }

        Company::query()->select(['id','name','country','industry','sector','company_type','organization_type','business_type'])
            ->chunkById(200, fn($companies) => $companies->each(fn(Company $company) => $this->classify($company)));
    }

    private function classify(Company $company): void
    {
        $text = Str::lower(implode(' ', array_filter([
            $company->name, $company->industry, $company->sector, $company->company_type,
            $company->organization_type, $company->business_type,
        ])));
        $names = [];
        if (Str::contains($text, ['software','product','saas','platform'])) $names[] = 'Product Companies';
        if (Str::contains($text, ['service','outsourc','staffing','bpo'])) $names[] = 'Service Companies';
        if (Str::contains($text, ['consult','advisory'])) $names[] = 'Consulting Companies';
        if (Str::contains($text, ['bank','financial','insurance','nbfc','investment'])) $names[] = 'Banking & Finance';
        if (Str::contains($text, ['fintech','payment'])) $names[] = 'Financial Technology';
        if (Str::contains($text, ['hotel','hospitality','travel','tourism','restaurant'])) $names[] = 'Hospitality & Travel';
        if (Str::contains($text, ['health','medical','pharma','biotech'])) $names[] = 'Healthcare Innovators';
        if (Str::contains($text, ['education','learning','edtech'])) $names[] = 'Education Technology';
        if (Str::contains($text, ['internet','web','online'])) $names[] = 'Internet Businesses';
        if (Str::contains($text, 'startup')) $names[] = 'Startups';
        if (Str::lower((string) $company->country) === 'india') $names[] = 'Indian Enterprises';
        elseif ($company->country || Str::contains($text, ['mnc','multinational'])) $names[] = 'Global Companies';

        $ids = CompanyCategory::where('taxonomy','collection')->whereIn('name',array_unique($names))->pluck('id');
        if ($ids->isNotEmpty()) $company->categories()->syncWithoutDetaching($ids);
    }
}
