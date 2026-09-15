<?php

namespace Database\Seeders;

use App\Services\IndustrialCsvImporter;
use Illuminate\Database\Seeder;

class IndustrialDirectorySeeder extends Seeder
{
    public function run(): void
    {
        $importer = app(IndustrialCsvImporter::class);

        /*
        |--------------------------------------------------------------------------
        | 1. States
        |--------------------------------------------------------------------------
        */
        $importer->import('states',storage_path('app/industrial-data/states.csv'));

        /*
        |--------------------------------------------------------------------------
        | 2. Industrial Areas / Industrial Parks / Clusters
        |--------------------------------------------------------------------------
        |
        | This is the master industrial-area directory.
        |
        | The CSV contains:
        | State
        | City
        | District
        | Industrial Area
        | Area Type
        | Sectors
        | Source
        | Verification information
        |
        */
        $importer->import('industrial_areas',storage_path('app/industrial-data/industrial_areas.csv'));
        /*
        |--------------------------------------------------------------------------
        | 3. Industrial Departments
        |--------------------------------------------------------------------------
        */
        $importer->import('industrial_departments',storage_path('app/industrial-data/industrial_departments.csv'));
        /*
        |--------------------------------------------------------------------------
        | 4. Industrial Job Roles
        |--------------------------------------------------------------------------
        */
        $importer->import('industrial_job_roles',storage_path('app/industrial-data/industrial_job_roles.csv'));
        /*
        |--------------------------------------------------------------------------
        | 5. Industrial Taxonomy
        |--------------------------------------------------------------------------
        |
        | Creates:
        | - sectors
        | - subsectors
        | - processes
        | - career dictionary
        | - job-role/process relationships
        | - industrial assets
        |
        */
        $this->call(IndustrialTaxonomySeeder::class);
        $importer->import('industrial_companies', storage_path('app/industrial-data/industrial_companies.csv'));
        $this->call([IndustrialVisualAssetSeeder::class, IndustrialCareerPathwaySeeder::class, IndustrialClusterEvidenceSeeder::class]);
    }
}
