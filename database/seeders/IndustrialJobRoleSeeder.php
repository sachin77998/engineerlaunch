<?php

namespace Database\Seeders;

use App\Services\IndustrialCsvImporter;
use Illuminate\Database\Seeder;

class IndustrialJobRoleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(IndustrialDepartmentSeeder::class);
        app(IndustrialCsvImporter::class)->import('industrial_job_roles', storage_path('app/industrial-data/industrial_job_roles.csv'));
    }
}
