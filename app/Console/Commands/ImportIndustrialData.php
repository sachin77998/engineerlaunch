<?php

namespace App\Console\Commands;

use App\Services\IndustrialCsvImporter;
use Illuminate\Console\Command;

class ImportIndustrialData extends Command
{
    protected $signature = 'industrial:import {type : states, industrial_areas, industrial_companies, industrial_departments, industrial_job_roles or industrial_jobs} {--path= : CSV path; defaults to storage/app/industrial-data/TYPE.csv} {--dry-run : Validate without saving changes}';

    protected $description = 'Validate and atomically import an industrial directory CSV using stable keys';

    public function handle(IndustrialCsvImporter $importer): int
    {
        try {
            $type = $this->argument('type');
            $count = $importer->import($type, $this->option('path') ?: storage_path('app/industrial-data/'.$type.'.csv'), (bool) $this->option('dry-run'));
            $this->info(($this->option('dry-run') ? 'Validated' : 'Imported').' '.$count.' rows'.($this->option('dry-run') ? '; no changes saved.' : '.'));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
