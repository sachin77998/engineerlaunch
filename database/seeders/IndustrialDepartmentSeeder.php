<?php

namespace Database\Seeders;

use App\Services\IndustrialCsvImporter;
use Illuminate\Database\Seeder;

class IndustrialDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        app(IndustrialCsvImporter::class)->import('industrial_departments', storage_path('app/industrial-data/industrial_departments.csv'));
    }
}
