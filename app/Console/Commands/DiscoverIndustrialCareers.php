<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{IndustrialCompany, Company};
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\{Uri, UriResolver};

class DiscoverIndustrialCareers extends Command
{
    protected $signature='industrial:discover-careers {--limit=50 : Maximum companies per batch} {--after=0 : Resume after this industrial company ID}';
    protected $description='Find career links on verified manufacturers, recording evidence without creating vacancies';

    public function handle(): int
    {
        $limit=max(1,min(500,(int)$this->option('limit')));
        $companies=IndustrialCompany::visible()->whereNotNull('website')->where('id','>',(int)$this->option('after'))->orderBy('id')->limit($limit)->get();
        $http=new Client(['timeout'=>20,'connect_timeout'=>8,'verify'=>config('industrial_sources.ca_bundle',true),'headers'=>['User-Agent'=>'AscendiaCareerDirectory/1.0']]);
        $failed=false;
        foreach ($companies as $company) {
            try {
                $page=$company->website;
                if (parse_url($page,PHP_URL_SCHEME)!=='https') throw new \RuntimeException('HTTPS official website required');
                $html=(string)$http->get($page)->getBody();
                $dom=new \DOMDocument();@$dom->loadHTML($html,LIBXML_NOERROR|LIBXML_NOWARNING);$xpath=new \DOMXPath($dom);
                $links=[];
                foreach ($xpath->query('//a[@href]') as $a) {
                    $href=trim($a->getAttribute('href'));
                    if (!preg_match('/career|opportunit|vacanc|job\b|join our team|work with us/i',$a->textContent.' '.$href)) continue;
                    $url=(string)UriResolver::resolve(new Uri($page),new Uri($href));
                    if (parse_url($url,PHP_URL_SCHEME)!=='https') continue;
                    $host=strtolower((string)parse_url($url,PHP_URL_HOST));
                    $home=strtolower((string)parse_url($page,PHP_URL_HOST));
                    if ($host!==$home && !preg_match('/\.(successfactors\.(com|eu)|myworkdayjobs\.com|darwinbox\.in|greenhouse\.io|lever\.co|smartrecruiters\.com)$/',$host)) continue;
                    $links[$url]=true;
                }
                foreach (array_keys($links) as $url) {
                    $company->sources()->updateOrCreate(['source_key'=>'career:'.sha1($url)],['title'=>'Official website career link','url'=>$url,'source_period'=>now()->toDateString(),'checked_at'=>now(),'evidence_note'=>'Discovered from '.$page.'. Career channel only; vacancies and parser support need verification.']);
                }
                $this->line($company->id.' | '.$company->name.' | '.count($links).' career links recorded');
            } catch (\Throwable $e) {
                $failed=true;
                $this->warn($company->id.' | '.$company->name.' | '.$e->getMessage());
            }
        }
        if ($companies->isNotEmpty()) $this->info('Next batch: --after='.$companies->last()->id);
        $this->info('No vacancies or company locations are inferred by discovery.');
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
