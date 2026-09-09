<?php

namespace App\Jobs;

use App\Mail\PortalEventMail;
use App\Models\PortalEventNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPortalEventEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public array $backoff = [30, 120, 300];

    public function __construct(public int $notificationId)
    {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        $notification = PortalEventNotification::with('event')->find($this->notificationId);
        if (! $notification || $notification->status === 'sent' || ! $notification->event) {
            return;
        }

        Mail::to($notification->email)->send(new PortalEventMail($notification->event));
        $updated = PortalEventNotification::whereKey($notification->id)
            ->where('status', '!=', 'sent')
            ->update(['status' => 'sent', 'sent_at' => now(), 'error_message' => null, 'updated_at' => now()]);
        if ($updated) {
            $notification->event->increment('emails_sent');
        }
    }

    public function failed(\Throwable $exception): void
    {
        $notification = PortalEventNotification::with('event')->find($this->notificationId);
        if (! $notification || $notification->status === 'sent') {
            return;
        }

        $notification->update(['status' => 'failed', 'error_message' => $exception->getMessage()]);
        $notification->event?->increment('emails_failed');
    }
}
