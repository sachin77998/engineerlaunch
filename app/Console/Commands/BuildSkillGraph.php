<?php

namespace App\Console\Commands;

use App\Services\Recommendations\SkillGraph;
use Illuminate\Console\Command;

class BuildSkillGraph extends Command
{
    protected $signature = 'recommendations:build-skill-graph';
    protected $description = 'Learn which skills appear together in job postings (used to recommend similar jobs)';

    public function handle(SkillGraph $graph): int
    {
        $result = $graph->build();
        $this->info("Learned {$result['pairs']} related skill pairs across {$result['skills']} skills from {$result['jobs']} job postings.");
        return self::SUCCESS;
    }
}
