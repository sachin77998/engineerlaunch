<?php

namespace App\Services;

use App\Jobs\SyncCompanyJobs;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * A search for a company that has a careers feed but no imported jobs yet (e.g. "zscaler") fetches that feed
 * on the queue (processed by the every-minute worker) instead of waiting for the nightly sync. Large feeds take
 * minutes, so this never runs inside the web request. Each company is queued at most once every six hours.
 */
class OnDemandCompanySync
{
    /** @return array{queued:array<int,string>} */
    public function forQuery(string $query): array
    {
        $result = ['queued' => []];
        $query = trim($query);
        if (mb_strlen($query) < 3) return $result;

        $key = Company::nameKey($query);
        // Most searches are skills or titles; remember "no unsynced company matches" for a while.
        $lookup = 'on-demand-sync:q:' . md5(mb_strtolower($query));
        if (Cache::has($lookup)) return $result;
        $companies = Company::active()->where('sync_enabled', true)->whereNotNull('ats_provider')
            ->where(function ($q) use ($query, $key) {
                $q->where('name', 'like', addcslashes($query, '%_\\') . '%');
                if ($key !== '' && Schema::hasColumn('companies', 'name_key')) $q->orWhere('name_key', $key);
            })
            ->whereDoesntHave('jobs', fn ($jobs) => $jobs->active())
            ->limit(3)->get();
        if ($companies->isEmpty()) { Cache::put($lookup, true, now()->addMinutes(30)); return $result; }

        foreach ($companies as $company) {
            if (!Cache::add('on-demand-sync:' . $company->id, true, now()->addHours(6))) continue;
            SyncCompanyJobs::dispatch((int) $company->id)->onConnection('database')->onQueue('ingestion');
            $result['queued'][] = $company->name;
        }
        return $result;
    }
}
