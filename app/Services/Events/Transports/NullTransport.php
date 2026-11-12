<?php

namespace App\Services\Events\Transports;

use App\Contracts\EventTransport;

class NullTransport implements EventTransport
{
    public function send(array $events): void {}

    public function name(): string { return 'null'; }
}
