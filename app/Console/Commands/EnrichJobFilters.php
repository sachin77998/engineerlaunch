<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Models\Technology;
use App\Services\JobClassificationService;
use App\Support\DiscoveryCache;
use Illuminate\Console\Command;

class EnrichJobFilters extends Command
{
    protected $signature = 'jobs:enrich-filters';
    protected $description = 'Build searchable technology and experience facets from active job content';

    public function handle(JobClassificationService $classifier): int
    {
        $technologies = Technology::query()->get(['id', 'name']);
        $processed = $linked = $experienced = 0;

        Job::query()->active()->with('company')->select(['jobs.*'])->chunkById(200, function ($jobs) use ($technologies, $classifier, &$processed, &$linked, &$experienced) {
            foreach ($jobs as $job) {
                $text = implode(' ', array_filter([$job->title, $job->description, is_array($job->requirements) ? implode(' ', $job->requirements) : $job->requirements]));
                $technologyIds = $technologies->filter(function ($technology) use ($text) {
                    $name = preg_quote($technology->name, '/');
                    return preg_match('/(?<![\pL\pN])'.$name.'(?![\pL\pN])/iu', $text) === 1;
                })->pluck('id');
                if ($technologyIds->isNotEmpty()) {
                    $job->technologies()->syncWithoutDetaching($technologyIds);
                    $linked += $technologyIds->count();
                }

                if (is_null($job->experience_min)) {
                    $classification = $classifier->classify($job->getAttributes(), $job->company);
                    if (array_key_exists('experience_min', $classification)) {
                        $job->updateQuietly(['experience_min'=>$classification['experience_min'], 'experience_max'=>$classification['experience_max']]);
                        $experienced++;
                    }
                }
                $processed++;
            }
        });

        DiscoveryCache::invalidate();
        $this->info("Processed {$processed} jobs; added {$linked} technology links; derived experience for {$experienced} jobs.");
        return self::SUCCESS;
    }
}
