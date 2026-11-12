<?php

namespace App\Console\Commands;

use App\Contracts\EventTransport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

// Outbox relay: forwards unpublished domain events to the configured transport (log / redis / kafka), oldest first,
// marks them published, retries failures with a growing delay, and prunes old published rows.
class RelayDomainEvents extends Command
{
    protected $signature = 'events:relay {--limit=}';

    protected $description = 'Forward outbox domain events to the event bus transport';

    public function handle(EventTransport $transport): int
    {
        $c = config('events_bus');
        $rows = DB::table('domain_events')->whereNull('published_at')->where('attempts', '<', $c['max_attempts'])
            ->where(fn ($q) => $q->whereNull('retry_at')->orWhere('retry_at', '<=', now()))
            ->orderBy('id')->limit((int) ($this->option('limit') ?: $c['batch']))->get();
        if ($rows->isEmpty()) { $this->prune($c); return self::SUCCESS; }
        $events = $rows->map(fn ($r) => ['id' => $r->id, 'topic' => $r->topic, 'key' => $r->key, 'payload' => json_decode($r->payload, true), 'occurred_at' => (string) $r->occurred_at])->all();
        try {
            $transport->send($events);
            DB::table('domain_events')->whereIn('id', $rows->pluck('id'))->update(['published_at' => now(), 'transport' => $transport->name(), 'last_error' => null, 'updated_at' => now()]);
            $this->info('Relayed ' . count($events) . ' events via ' . $transport->name() . '.');
        } catch (Throwable $e) {
            report($e);
            foreach ($rows as $r) {
                DB::table('domain_events')->where('id', $r->id)->update(['attempts' => $r->attempts + 1, 'last_error' => substr($e->getMessage(), 0, 500),
                    'retry_at' => now()->addSeconds(min(3600, 30 * 2 ** $r->attempts)), 'updated_at' => now()]);
            }
            $this->error('Relay failed: ' . $e->getMessage());
            return self::FAILURE;
        }
        $this->prune($c);
        return self::SUCCESS;
    }

    private function prune(array $c): void
    {
        DB::table('domain_events')->whereNotNull('published_at')->where('published_at', '<', now()->subDays($c['keep_days']))->limit(5000)->delete();
    }
}
