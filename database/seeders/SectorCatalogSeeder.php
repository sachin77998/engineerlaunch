<?php

namespace Database\Seeders;

use App\Services\SectorCatalogImporter;
use App\Services\SectorDirectory;
use Illuminate\Database\Seeder;

class SectorCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $totals = app(SectorCatalogImporter::class)->importAll();
        SectorDirectory::flush();
        $this->command?->info('Sector catalog: ' . json_encode($totals));
    }
}
