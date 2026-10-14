<?php

namespace App\Console\Commands;

use App\Support\JobRoles;
use Illuminate\Console\Command;

class RefreshJobRoleCounts extends Command
{
    protected $signature = 'jobs:role-counts';
    protected $description = 'Recount active jobs per role for the homepage "Browse jobs by role" chips';

    public function handle(): int
    {
        foreach (JobRoles::refreshCounts() as $role => $count) $this->line(str_pad($role, 24) . $count);
        return self::SUCCESS;
    }
}
