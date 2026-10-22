<?php

namespace App\Jobs;

use App\Mail\HiringMail;
use App\Models\HiringMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

// Sends one recruiter email to a candidate and records whether it went out.
class SendHiringMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300];

    public function __construct(public int $messageId, public array $files = [])
    {
    }

    public function handle(): void
    {
        $m = HiringMessage::with('sender:id,email')->find($this->messageId);
        if (!$m || $m->status === 'sent') return;
        Mail::to($m->to_email)->send(new HiringMail($m->subject, $m->body, $m->sender?->email, $this->files));
        $m->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
    }

    public function failed(Throwable $e): void
    {
        HiringMessage::whereKey($this->messageId)->update(['status' => 'failed', 'error' => Str::limit($e->getMessage(), 250)]);
    }
}
