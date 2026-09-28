<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CyberCityCompanySeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('cyber_city.companies', []) as $entry) {
            $company = Company::firstOrNew(['name' => $entry['name']]);
            if (! $company->exists) {
                $company->fill([
                    'slug' => Str::slug($entry['name']), 'country' => 'India',
                    'industry' => $entry['industry'], 'is_active' => true, 'sync_enabled' => false,
                ]);
            }
            $company->fill(['website' => $entry['website'], 'careers_url' => $entry['careers_url']]);
            $company->save();
        }
        \App\Support\DiscoveryCache::invalidate();
    }
}
