<?php

namespace App\Console\Commands;

use App\Services\Insights\CreditOutlookModel;
use Illuminate\Console\Command;

// Retrains the credit outlook model (next rating action stable/better vs downgrade) from stored rating histories.
class TrainCreditModel extends Command
{
    protected $signature = 'insights:credit-model';
    protected $description = 'Train the credit outlook model used for borrowing-capacity verdicts';

    public function handle(CreditOutlookModel $model): int
    {
        $r = $model->train();
        $this->info($r['ok'] ? "Trained on {$r['pairs']} rating pairs; held-out AUC " . ($r['test_auc'] ?? '—') . ', accuracy ' . ($r['test_accuracy'] ?? '—') . '%' : $r['message']);
        return self::SUCCESS;
    }
}
