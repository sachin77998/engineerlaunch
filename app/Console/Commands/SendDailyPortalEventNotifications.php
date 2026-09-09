<?php

namespace App\Console\Commands;

use App\Jobs\SendPortalEventEmailJob;
use App\Models\PortalEvent;
use App\Models\PortalEventNotification;
use App\Models\User;
use Illuminate\Console\Command;

class SendDailyPortalEventNotifications extends Command
{
    protected $signature = 'portal:send-daily-events {--limit=100 : Maximum users queued in this batch}';
    protected $description = 'Queue one due portal event email per eligible user';

    public function handle(): int
    {
        $requestedLimit = max(1, min(1000, (int) $this->option('limit')));
        $event = PortalEvent::query()
            ->where('is_active', true)
            ->where('email_enabled', true)
            ->where(fn ($query) => $query->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('sent_at')->orWhereDate('sent_at', '<', today()))
            ->orderByRaw('scheduled_at IS NULL')
            ->orderBy('scheduled_at')
            ->first();

        if (! $event) {
            $this->info('No event available for today.');
            return self::SUCCESS;
        }

        $limit = min($requestedLimit, max(1, $event->target_users));
        $users = User::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereDoesntHave('eventNotifications', fn ($query) => $query->where('portal_event_id', $event->id))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $queued = 0;
        foreach ($users as $user) {
            $notification = PortalEventNotification::firstOrCreate(
                ['portal_event_id' => $event->id, 'user_id' => $user->id],
                ['email' => $user->email, 'status' => 'pending']
            );
            if ($notification->wasRecentlyCreated) {
                SendPortalEventEmailJob::dispatch($notification->id);
                $queued++;
            }
        }

        $event->update(['sent_at' => now()]);
        $this->info('Event: '.$event->title);
        $this->info("Emails queued: {$queued}");

        return self::SUCCESS;
    }
}
