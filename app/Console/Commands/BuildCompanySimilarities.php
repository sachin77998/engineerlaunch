<?php

namespace App\Console\Commands;

use App\Services\Insights\CompanySimilarity;
use Illuminate\Console\Command;

class BuildCompanySimilarities extends Command
{
    protected $signature = 'insights:similar {--top=12 : Similar companies kept per company} {--max=25000 : Companies in the model}';
    protected $description = 'Rebuild the similar-companies recommender (TF-IDF content, financial profile and viewed-together signals)';

    public function handle(CompanySimilarity $model): int
    {
        ini_set('memory_limit', '1024M');
        $started = microtime(true);
        $model->build((int) $this->option('top'), (int) $this->option('max'), fn ($m) => $this->line($m));
        $this->info('Done in ' . round(microtime(true) - $started, 1) . 's.');
        return self::SUCCESS;
    }
}
