<?php
namespace App\Console\Commands;
use App\Services\CareerOpportunitySearch;
use Illuminate\Console\Command;
class DiscoverCareerOpportunities extends Command {
    protected $signature = 'careers:discover {--source-url= : Registered official careers or feed URL} {--filters={} : JSON object with country, state, city, industry, company, role and other filters} {--refresh : Refresh the registered source before searching}';
    protected $description = 'Search career tracks, factory profiles and current vacancies using dynamic filters';
    public function handle(CareerOpportunitySearch $service): int {
        try {
            $filters=json_decode($this->option('filters'),true,512,JSON_THROW_ON_ERROR);
            if (!is_array($filters)) throw new \InvalidArgumentException('Filters must be a JSON object.');
            if ($this->option('source-url')) $filters['source_url']=$this->option('source-url');
            if ($this->option('refresh')) {
                if (!$this->option('source-url')) throw new \InvalidArgumentException('--refresh requires --source-url.');
                $result=$service->refreshSource($this->option('source-url'));
                if (!$result['success']) { $this->error(implode('; ',$result['errors'])); return self::FAILURE; }
            }
            $this->line(json_encode($service->search($filters),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        } catch (\Throwable $e) { $this->error($e->getMessage()); return self::FAILURE; }
    }
}
