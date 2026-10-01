<?php
namespace App\Services;

use App\Models\Company;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;

/** Bounded discovery of JobPosting data and linked public ATS boards. */
class OfficialCareerDiscovery
{
    private array $robots = [];
    private const ATS = ['myworkdayjobs.com','greenhouse.io','lever.co','smartrecruiters.com','oraclecloud.com'];

    public function scan(Company $company): array
    {
        $client = new Client(['timeout'=>15,'connect_timeout'=>5,'verify'=>config('industrial_sources.ca_bundle',true),
            'headers'=>['User-Agent'=>'EngineerLaunchJobs/1.0'],'allow_redirects'=>false]);
        $queue = [$company->careers_url ?: $company->website];
        $seen=[]; $jobs=[]; $boards=[]; $errors=[];
        while ($queue && count($seen)<30) {
            $url=array_shift($queue);
            if (!$url || isset($seen[$url]) || !$this->allowed($url,$company)) continue;
            $seen[$url]=true;
            try {
                if (!$this->robotsAllow($client,$url)) { $errors[]='robots.txt disallows '.$url; continue; }
                $response=$client->get($url,['http_errors'=>false]);
                if ($response->getStatusCode()>=300 && $response->getStatusCode()<400) {
                    $queue[]=$this->absolute($url,$response->getHeaderLine('Location')); continue;
                }
                if ($response->getStatusCode()!==200) { $errors[]='HTTP '.$response->getStatusCode().' at '.$url; continue; }
                if (!str_contains(strtolower($response->getHeaderLine('Content-Type')),'text/html')) continue;
                $html=(string)$response->getBody();
                foreach (app(IndustrialCareerParser::class)->structured($html,$url) as $job) {
                    if ($this->allowed($job['external_url'],$company)) $jobs[$job['external_url']]=$job;
                }
                $board=$this->board($url,$html);
                if ($board) { $boards[json_encode($board)]=$board; continue; }
                $dom=new \DOMDocument(); @$dom->loadHTML($html,LIBXML_NOERROR|LIBXML_NOWARNING);
                $xpath=new \DOMXPath($dom);
                foreach ($xpath->query('//a[@href]') as $anchor) {
                    $href=$this->absolute($url,html_entity_decode($anchor->getAttribute('href')));
                    if (!$this->allowed($href,$company) || isset($seen[$href])) continue;
                    $board=$this->board($href,'');
                    if ($board) { $boards[json_encode($board)]=$board; continue; }
                    if (!preg_match('~career|/jobs?[/?.-]|vacanc|opportunit|karriere|stellen|recrui|emploi~i',$href.' '.$anchor->textContent)) continue;
                    if (count($queue)<100 && !in_array($href,$queue,true)) $queue[]=$href;
                }
            } catch (\Throwable $e) { $errors[]='Could not read '.$url.' ('.get_class($e).')'; }
        }
        return ['jobs'=>array_values($jobs),'boards'=>array_values($boards),'pages'=>count($seen),'errors'=>array_slice($errors,0,5)];
    }

    public function board(string $url,string $html): ?array
    {
        $host=strtolower(parse_url($url,PHP_URL_HOST) ?? '');
        $path=parse_url($url,PHP_URL_PATH) ?? '/';
        if (preg_match('/^([a-z0-9-]+)\.wd\d+\.myworkdayjobs\.com$/',$host,$match)) {
            $parts=array_values(array_filter(explode('/',$path)));
            if (isset($parts[0]) && preg_match('/^[a-z]{2}-[A-Z]{2}$/',$parts[0])) array_shift($parts);
            if (!empty($parts[0])) return ['ats_provider'=>'workday','jobs_feed_url'=>'https://'.$host.'/wday/cxs/'.$match[1].'/'.$parts[0].'/jobs','careers_url'=>'https://'.$host.'/'.$parts[0]];
        }
        if (str_ends_with($host,'.oraclecloud.com') && preg_match('~(/hcmUI/CandidateExperience/[^/]+/sites/([^/?]+))~i',$path,$m))
            return ['ats_provider'=>'oracle_recruiting','ats_identifier'=>$m[2],'careers_url'=>'https://'.$host.$m[1],
                'jobs_feed_url'=>'https://'.$host.'/hcmRestApi/resources/latest/recruitingCEJobRequisitions'];
        if (in_array($host,['boards.greenhouse.io','job-boards.greenhouse.io'],true) && preg_match('~^/([a-zA-Z0-9_-]+)~',$path,$m))
            return ['ats_provider'=>'greenhouse','ats_identifier'=>$m[1],'careers_url'=>'https://'.$host.'/'.$m[1]];
        if ($host==='jobs.lever.co' && preg_match('~^/([a-zA-Z0-9_-]+)~',$path,$m))
            return ['ats_provider'=>'lever','ats_identifier'=>$m[1],'careers_url'=>'https://'.$host.'/'.$m[1]];
        if (in_array($host,['jobs.smartrecruiters.com','careers.smartrecruiters.com'],true) && preg_match('~^/([a-zA-Z0-9_-]+)~',$path,$m))
            return ['ats_provider'=>'smartrecruiters','ats_identifier'=>$m[1],'careers_url'=>'https://'.$host.'/'.$m[1]];
        if ($html && (str_contains($html,'jobTitle-link') || str_contains($html,'xweb/rmk-jobs-search')))
            return ['ats_provider'=>'successfactors','jobs_feed_url'=>'https://'.$host.'/search/?q=','careers_url'=>'https://'.$host.'/'];
        return null;
    }

    public function allowed(string $url,Company $company): bool
    {
        $parts=parse_url($url);
        if (!$parts || ($parts['scheme'] ?? '')!=='https' || isset($parts['user']) || isset($parts['port'])) return false;
        $host=strtolower($parts['host'] ?? '');
        if (!$host || filter_var($host,FILTER_VALIDATE_IP)) return false;
        $hosts=array_filter(array_map(fn($value)=>preg_replace('/^www\./','',strtolower(parse_url($value ?: '',PHP_URL_HOST) ?? '')),[$company->website,$company->careers_url]));
        $allowed=false;
        foreach (array_merge($hosts,self::ATS) as $base) if ($host===$base || str_ends_with($host,'.'.$base)) $allowed=true;
        if (!$allowed) return false;
        foreach (gethostbynamel($host) ?: [] as $ip)
            if (!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)) return false;
        return true;
    }

    private function absolute(string $base,string $href): string
    {
        try { return (string)UriResolver::resolve(new Uri($base),new Uri($href))->withFragment(''); }
        catch (\Throwable $e) { return ''; }
    }

    protected function robotsAllow(Client $client,string $url): bool
    {
        $origin='https://'.parse_url($url,PHP_URL_HOST);
        if (!array_key_exists($origin,$this->robots)) {
            $r=$client->get($origin.'/robots.txt',['http_errors'=>false]);
            if ($r->getStatusCode()===404) $this->robots[$origin]=[];
            elseif ($r->getStatusCode()!==200) return false;
            else {
                $groups=[]; $group=['agents'=>[],'rules'=>[]];
                foreach (preg_split('/\R/',(string)$r->getBody()) as $line) {
                    $line=trim(explode('#',$line,2)[0]);
                    if (preg_match('/^User-agent:\s*(.+)$/i',$line,$m)) {
                        if ($group['rules']) { $groups[]=$group; $group=['agents'=>[],'rules'=>[]]; }
                        $group['agents'][]=strtolower(trim($m[1]));
                    } elseif ($group['agents'] && preg_match('/^(Allow|Disallow):\s*(.*)$/i',$line,$m)) {
                        if (trim($m[2])!=='') $group['rules'][]=[strtolower($m[1]),trim($m[2])];
                    }
                }
                if ($group['agents']) $groups[]=$group;
                $rules=[]; $specificity=-1;
                foreach ($groups as $group) {
                    $rank=-1;
                    foreach ($group['agents'] as $agent) {
                        if ($agent==='*') $rank=max($rank,0);
                        elseif (str_contains('engineerlaunchjobs/1.0',$agent)) $rank=max($rank,strlen($agent));
                    }
                    if ($rank<0 || $rank<$specificity) continue;
                    if ($rank>$specificity) $rules=[];
                    $specificity=$rank;
                    $rules=array_merge($rules,$group['rules']);
                }
                $this->robots[$origin]=$rules;
            }
        }
        $path=(parse_url($url,PHP_URL_PATH) ?: '/').(parse_url($url,PHP_URL_QUERY) ? '?'.parse_url($url,PHP_URL_QUERY) : '');
        $best=-1; $allow=true;
        foreach ($this->robots[$origin] as [$type,$pattern]) {
            $regex=str_replace(['\*','\$'],['.*','$'],preg_quote($pattern,'~'));
            if (preg_match('~^'.$regex.'~',$path) && strlen($pattern)>=$best) {
                if (strlen($pattern)>$best || $type==='allow') $allow=$type==='allow';
                $best=strlen($pattern);
            }
        }
        return $allow;
    }
}
