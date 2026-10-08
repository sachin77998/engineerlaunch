<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class CompanyCatalogImported
{
    use Dispatchable;

    public function __construct(public ?int $batchRunId, public bool $syncOpenings, public array $totals = []) {}
}
