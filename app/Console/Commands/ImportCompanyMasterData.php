<?php

namespace App\Console\Commands;

use App\Services\Insights\MasterDataImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportCompanyMasterData extends Command
{
    protected $signature = 'insights:import-master {file : CSV path, absolute or relative to storage/app} {--delete : Delete the file after a successful import}';
    protected $description = 'Import MCA Company Master Data (data.gov.in CSV) into companies and their registry details';

    public function handle(MasterDataImporter $importer): int
    {
        @ini_set('memory_limit', '1024M');
        $file = $this->argument('file');
        $path = is_file($file) ? $file : Storage::path($file);
        if (!is_file($path)) { $this->error("File not found: {$file}"); return self::FAILURE; }
        $totals = $importer->import($path);
        $this->info("Read {$totals['rows']} companies: {$totals['created']} new, {$totals['detailed']} with registry details saved.");
        if ($this->option('delete')) @unlink($path);
        return self::SUCCESS;
    }
}
