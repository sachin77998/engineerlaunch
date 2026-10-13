<?php

namespace App\Console\Commands;

use App\Services\SectorDirectory;
use Illuminate\Console\Command;

class WarmSectors extends Command
{
    protected $signature = 'sectors:warm';
    protected $description = 'Rebuild the Companies by Sector cards (counts, logos, openings) from the latest data';

    public function handle(SectorDirectory $directory): int
    {
        @ini_set('memory_limit', '1024M');
        SectorDirectory::flush();
        $groups = $directory->groups();
        $this->info('Rebuilt ' . $groups->count() . ' sector categories.');
        return self::SUCCESS;
    }
}
