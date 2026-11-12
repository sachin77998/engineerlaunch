<?php

namespace App\Services\Events\Transports;

use App\Contracts\EventTransport;
use Illuminate\Support\Facades\Log;

class LogTransport implements EventTransport
{
    public function send(array $events): void
    {
        foreach ($events as $e) Log::info('event ' . $e['topic'], ['id' => $e['id'], 'key' => $e['key'], 'payload' => $e['payload']]);
    }

    public function name(): string { return 'log'; }
}
