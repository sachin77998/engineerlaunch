<?php

namespace App\Console\Commands;

use App\Services\Property\PropertyAlerts;
use Illuminate\Console\Command;

class SendPropertyAlertDigests extends Command
{
    protected $signature = 'property-alerts:digest';
    protected $description = 'Email due daily and weekly summaries of new listings to saved searches';

    public function handle(PropertyAlerts $alerts): int
    {
        foreach (['daily', 'weekly'] as $f) $this->line($f . ': ' . $alerts->digest($f) . ' emails');
        return self::SUCCESS;
    }
}
