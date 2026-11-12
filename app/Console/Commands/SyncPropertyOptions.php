<?php

namespace App\Console\Commands;

use App\Services\Property\PropertyOptions;
use Illuminate\Console\Command;

// Creates tables for option groups newly added to config/property_options.php and seeds them; existing rows are never touched.
class SyncPropertyOptions extends Command
{
    protected $signature = 'property-options:sync';

    protected $description = 'Create and seed database tables for property option lists';

    public function handle(PropertyOptions $options): int
    {
        foreach ($options->sync() as $group => $result) $this->line(str_pad($group, 34) . $result);
        return self::SUCCESS;
    }
}
