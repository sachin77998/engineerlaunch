<?php

namespace App\Console\Commands;

use App\Services\JobGeography;
use Illuminate\Console\Command;

class RefreshJobPlaces extends Command
{
    protected $signature = 'jobs:refresh-places';
    protected $description = 'Resolve every active job location once and cache it for the location filters';

    public function handle(JobGeography $geography): int
    {
        @ini_set('memory_limit', '1024M');
        $this->info(count($geography->refreshPlaces()) . ' job locations cached.');
        return self::SUCCESS;
    }
}
