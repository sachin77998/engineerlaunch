<?php

namespace App\Jobs;

use App\Services\SectorDirectory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/** Builds the Companies-by-Sector category cards in the background so web requests never wait for it. */
class WarmSectorGroups implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 900;
    public int $uniqueFor = 900;

    public function handle(SectorDirectory $directory): void
    {
        ini_set('memory_limit', '1024M');
        $directory->groups();
    }
}
