<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportIndustrialAreas extends Command
{
    protected $signature = 'industrial:import-areas {--file=industrial-data/industrial_areas.csv} {--dry-run}';

    protected $description = 'Import the industrial area master from a CSV under storage/app';

    public function handle(): int
    {
        $path = realpath(storage_path('app/'.$this->option('file')));
        $base = realpath(storage_path('app'));
        if (! $path || ! $base || ! str_starts_with(str_replace('\\', '/', $path), str_replace('\\', '/', $base).'/')) {
            $this->error('Choose an existing CSV within storage/app.');

            return self::FAILURE;
        }

        return $this->call('industrial:import', ['type' => 'industrial_areas', '--path' => $path, '--dry-run' => $this->option('dry-run')]);
    }
}
