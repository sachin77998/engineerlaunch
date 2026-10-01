<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RequestedEmployerInventorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('requested_employers', []) as $source) {
            if (empty($source['name']) || empty($source['website']) || empty($source['careers_url'])) {
                continue;
            }
            $company = Company::firstOrNew(['name' => $source['name'],]);
            $company->forceFill([
                'slug' => $company->slug ?: Str::slug($source['name']),
                'website' => $source['website'],
                'careers_url' => $source['careers_url'],
                'country' => $source['country'] ?? 'India',
                'industry' => $source['industry'] ?? null,
                'sector' => $source['industry'] ?? null,
                'is_active' => true,
                'sync_enabled' => true,
            ]);
            if (blank($company->ats_provider)) {
                $company->ats_provider = 'official_discovery';
            }
            $company->save();
        }
        $this->call(RequestedCompanySourceSeeder::class);
    }
}
