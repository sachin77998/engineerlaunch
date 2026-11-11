<?php

namespace App\Console\Commands;

use App\Services\Insights\FundingRoundImporter;
use Illuminate\Console\Command;

class ImportFundingRounds extends Command
{
    protected $signature = 'insights:funding-rounds {--all : Re-read every news article, not only those since the last run} {--limit=5000 : Maximum articles read per run}';
    protected $description = 'Read equity funding rounds and their investors from funding news';

    public function handle(FundingRoundImporter $importer): int
    {
        $stats = $importer->run((bool) $this->option('all'), max(1, (int) $this->option('limit')), fn ($m) => $this->line($m));
        $this->info("Read {$stats['scanned']} articles: {$stats['rounds']} new rounds, {$stats['merged']} more reports of known rounds.");
        return self::SUCCESS;
    }
}
