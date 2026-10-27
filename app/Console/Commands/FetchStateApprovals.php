<?php

namespace App\Console\Commands;

use App\Services\Approvals\StateApprovalImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FetchStateApprovals extends Command
{
    protected $signature = 'approvals:fetch {--state=* : Only these state codes (e.g. UK)} {--limit= : Read only the first N services (testing)} {--reparse : Re-parse stored checklist text without downloading}';
    protected $description = 'Fetch state single-window services, document checklists, policies and acts';

    public function handle(StateApprovalImporter $importer): int
    {
        $added = $importer->seedSources();
        if ($added) $this->info("Added $added state portal source(s).");
        $codes = array_map('strtoupper', $this->option('state'));
        if ($this->option('reparse')) {
            $ids = $codes ? DB::table('industrial_states')->whereIn('code', $codes)->pluck('id') : collect([null]);
            $n = $ids->sum(fn ($id) => $importer->reparse($id));
            $this->info("Re-parsed $n checklist(s).");
            return self::SUCCESS;
        }
        $sources = DB::table('state_approval_sources as s')->join('industrial_states as st', 'st.id', '=', 's.state_id')
            ->where('s.is_active', true)->whereNotNull('s.driver')->when($codes, fn ($q) => $q->whereIn('st.code', $codes))
            ->select('s.*', 'st.name as state_name')->get();
        foreach ($sources as $s) {
            $this->line("<info>{$s->state_name}</info> — {$s->portal_name}");
            $r = $importer->run($s, fn ($m) => $this->line($m), $this->option('limit') ? (int) $this->option('limit') : null);
            $this->line('  → ' . $r['status']);
        }
        if ($sources->isEmpty()) $this->warn('No active state portal with a reader matched.');
        // Documents and steps from department manuals are kept up to date with the config after every fetch.
        $this->info('Manual-sourced services refreshed: ' . app(\App\Services\Approvals\ManualApprovals::class)->apply());
        return self::SUCCESS;
    }
}
