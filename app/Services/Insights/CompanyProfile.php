<?php

namespace App\Services\Insights;

use App\Models\Company;
use App\Models\CompanyCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Profile sections of a company page: operational areas (sector paths) and competitors in the same sub-sectors. */
class CompanyProfile
{
    /** Sector › sub-sector paths the company is filed under. @return array<int, array{0:string,1:string,2:string}> name, sub, url */
    public function operationalAreas(Company $company): array
    {
        return CompanyCategory::query()->join('company_category_company as cc', 'cc.company_category_id', '=', 'company_categories.id')
            ->where('cc.company_id', $company->id)->whereNotNull('company_categories.parent_id')->where('company_categories.taxonomy', 'sector')
            ->where('company_categories.name', '!=', 'Discovered Companies')->with('parent:id,name,slug')->get(['company_categories.id', 'company_categories.name', 'company_categories.slug', 'company_categories.parent_id'])
            ->filter(fn ($c) => $c->parent)->map(fn ($c) => [$c->parent->name, $c->name, rescue(fn () => route('sectors.subcategory', [$c->parent->slug, $c->slug]), null, false)])->unique(fn ($a) => $a[0] . $a[1])->values()->all();
    }

    private function country(Company $c): string
    {
        $country = strtolower(trim((string) ($c->country ?: 'India')));
        return in_array($country, ['usa', 'us', 'united states of america'], true) ? 'united states' : $country;
    }

    /** Up to $limit companies sharing a sub-sector: ones with results or a market cap first. */
    public function competitors(Company $company, int $limit = 10): Collection
    {
        return \App\Support\Stale::remember('profile:competitors:' . $company->id, now()->addHours(12), function () use ($company, $limit) {
            $categories = DB::table('company_category_company as cc')->join('company_categories as c', 'c.id', '=', 'cc.company_category_id')
                ->where('cc.company_id', $company->id)->whereNotNull('c.parent_id')->where('c.taxonomy', 'sector')->where('c.name', '!=', 'Discovered Companies')->pluck('c.id');
            if ($categories->isEmpty()) return collect();
            $ids = DB::table('company_category_company')->whereIn('company_category_id', $categories)->where('company_id', '!=', $company->id)->distinct()->limit(400)->pluck('company_id');
            $home = $this->country($company);
            $revenue = DB::table('company_financial_statements')->whereIn('company_id', $ids)->selectRaw('company_id, MAX(revenue) as revenue')->groupBy('company_id')->pluck('revenue', 'company_id');
            $cap = DB::table('us_listed_companies')->whereIn('company_id', $ids)->selectRaw('company_id, MAX(market_cap) as cap')->groupBy('company_id')->pluck('cap', 'company_id');
            return Company::whereIn('id', $ids)->where('is_active', true)->with('registryDetail:id,company_id,incorporation_date,city', 'usListings:id,company_id,country,ipo_year,market_cap')
                ->get(['id', 'name', 'slug', 'website', 'logo_url', 'description', 'industry', 'sector', 'country', 'registered_state', 'headquarters'])
                // Same country first, then companies with results or a market cap, largest first.
                ->sortByDesc(fn ($c) => [$this->country($c) === $home ? 1 : 0, ($cap[$c->id] ?? 0) > 0 || ($revenue[$c->id] ?? 0) > 0 ? 1 : 0, (float) ($cap[$c->id] ?? 0) * 85 + (float) ($revenue[$c->id] ?? 0), $c->website ? 1 : 0])
                ->take($limit)->values()->map(function (Company $c) {
                    $us = $c->usListings->first();
                    return (object) ['name' => $c->name, 'slug' => $c->slug, 'website' => $c->website, 'logo' => $c->logo_url,
                        'description' => $c->description ?: trim(($c->sector ?? '') . ($c->industry && $c->industry !== $c->sector ? ' · ' . $c->industry : '')),
                        'founded' => $c->registryDetail?->incorporation_date?->format('Y') ?? $us?->ipo_year,
                        'location' => $us?->country ?? collect([$c->registryDetail?->city ? ucwords(strtolower($c->registryDetail->city)) : null, $c->registered_state, $c->country ?: 'India'])->filter()->unique()->implode(', '),
                        'region' => $us?->country ?? ($c->registered_state ?: ($c->country ?: 'India'))];
                });
        });
    }
}
