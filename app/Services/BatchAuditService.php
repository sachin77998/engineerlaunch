<?php

namespace App\Services;

use App\Models\BatchRun;
use App\Models\BatchRunItem;
use Illuminate\Database\QueryException;
use Throwable;

class BatchAuditService
{
    public function startItem(int $runId, int $companyId): BatchRunItem
    {
        $item = BatchRunItem::where('batch_run_id', $runId)->where('company_id', $companyId)->firstOrFail();
        $item->update([
            'status' => 'running',
            'attempt' => $item->attempt + 1,
            'started_at' => $item->started_at ?: now(),
            'failure_stage' => null,
            'failure_reason' => null,
        ]);
        return $item;
    }

    public function completeItem(BatchRunItem $item, array $result): void
    {
        $finished = now();
        $item->update([
            'status' => 'successful',
            'records_found' => $result['jobs_found'] ?? 0,
            'records_created' => $result['jobs_added'] ?? 0,
            'records_updated' => $result['jobs_updated'] ?? 0,
            'context' => ['warnings' => $result['errors'] ?? []],
            'finished_at' => $finished,
            'duration_ms' => $item->started_at ? $item->started_at->diffInMilliseconds($finished) : null,
        ]);
        $this->refreshRun($item->batch_run_id);
    }

    public function failItem(int $runId, int $companyId, Throwable $exception, string $stage): void
    {
        $item = BatchRunItem::where('batch_run_id', $runId)->where('company_id', $companyId)->first();
        if (!$item) return;
        $query = $this->queryDetails($exception);
        $finished = now();
        $item->update([
            'status' => 'failed',
            'failure_stage' => $stage,
            'failure_reason' => $exception->getMessage(),
            'failed_query' => $query['query'],
            'sql_state' => $query['sql_state'],
            'context' => ['exception' => get_class($exception), 'file' => $exception->getFile(), 'line' => $exception->getLine()],
            'finished_at' => $finished,
            'duration_ms' => $item->started_at ? $item->started_at->diffInMilliseconds($finished) : null,
        ]);
        $this->refreshRun($runId);
    }

    public function refreshRun(int $runId, bool $finished = false): void
    {
        $run = BatchRun::find($runId);
        if (!$run) return;
        $items = $run->items()->get();
        $failed = $items->where('status', 'failed');
        $pending = $items->whereIn('status', ['pending', 'running']);
        $values = [
            'successful_items' => $items->where('status', 'successful')->count(),
            'failed_items' => $failed->count(),
            'pending_items' => $pending->count(),
            'records_found' => $items->sum('records_found'),
            'records_created' => $items->sum('records_created'),
            'records_updated' => $items->sum('records_updated'),
        ];
        if ($finished || $pending->isEmpty()) {
            $ended = now();
            $status = $pending->isNotEmpty()
                ? 'incomplete'
                : ($failed->isEmpty() ? 'successful' : ($values['successful_items'] ? 'partially_failed' : 'failed'));
            $values += [
                'status' => $status,
                'failure_stage' => $failed->first()?->failure_stage ?: ($pending->isNotEmpty() ? 'queue_finalization' : null),
                'failure_reason' => $failed->first()?->failure_reason ?: ($pending->isNotEmpty() ? $pending->count().' item(s) did not reach a final status.' : null),
                'failed_query' => $failed->first()?->failed_query,
                'sql_state' => $failed->first()?->sql_state,
                'finished_at' => $ended,
                'duration_ms' => $run->started_at ? $run->started_at->diffInMilliseconds($ended) : null,
            ];
        }
        $run->update($values);
    }

    private function queryDetails(Throwable $exception): array
    {
        $current = $exception;
        while ($current) {
            if ($current instanceof QueryException) {
                return ['query' => $current->getSql(), 'sql_state' => $current->errorInfo[0] ?? null];
            }
            $current = $current->getPrevious();
        }
        return ['query' => null, 'sql_state' => null];
    }
}
