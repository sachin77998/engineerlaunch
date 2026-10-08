<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ExternalJobsImported
{
    use Dispatchable;

    public function __construct(public int $batchRunId) {}
}
