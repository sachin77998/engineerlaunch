<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\Company;
use App\Services\JobScraper;

class IndustrialSources extends Command
{
    protected $signature = 'industrial:sources {--sync : Fetch configured official feeds}';
    protected $description = 'Report industrial recruitment source coverage and optionally sync official vacancies';
    public function handle(): int
    {
        $rows=[]; $failed=false;
        foreach (config('industrial_sources.feeds') as $source) {
            $company=Company::where('name',$source['name'])->first();
            $status=$company ? 'Configured' : 'Run IndustrialRecruitmentSeeder';
            if ($company && $this->option('sync')) {
                $result=app(JobScraper::class)->scrapeCompany($company);
                $status=$result['success'] ? 'Fetched '.$result['jobs_found'] : implode('; ',$result['errors']);
                $failed=$failed || !$result['success'];
            }
            $rows[]=[$source['name'],$company ? $company->jobs()->active()->count() : 0,$company?->fresh()->last_synced_at ?? 'Never',$status];
        }
        $this->table(['Official employer','Active jobs in database','Last successful sync','Status'],$rows);
        $this->table(['Research source','Region','Coverage limitation'],collect(config('industrial_sources.research'))->map(fn($s)=>[$s['name'],$s['region'],$s['status']])->all());
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
