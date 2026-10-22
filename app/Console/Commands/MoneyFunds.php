<?php

namespace App\Console\Commands;

use App\Services\Money\FundModel;
use Illuminate\Console\Command;

// Keeps the fund model fresh: fetches NAV history in batches, retrains, and rescores buy/hold/exit signals.
class MoneyFunds extends Command
{
    protected $signature = 'money:funds {--fetch=150 : Funds to download history for in this run} {--train : Retrain the model} {--score : Rescore every fund}';
    protected $description = 'Fetch weekly NAV history, train the fund ranking model and score timing signals';

    public function handle(FundModel $model): int
    {
        if (($n = (int) $this->option('fetch')) > 0) $this->info('History refreshed for ' . $model->fetchBatch($n, fn ($m) => $this->line($m)) . ' funds.');
        if ($this->option('train')) {
            $r = $model->train();
            $this->info($r['ok'] ? "Trained on {$r['samples']} samples from {$r['funds']} funds; held-out rank IC " . ($r['test_rank_ic'] ?? '—') . ', top-quintile hit rate ' . ($r['test_top_quintile_hit_rate'] ?? '—') . '%' : $r['message']);
        }
        if ($this->option('score')) $this->info('Scored ' . $model->score() . ' funds.');
        return self::SUCCESS;
    }
}
