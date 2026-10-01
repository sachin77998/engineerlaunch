<?php
namespace App\Services;

use App\Models\Company;

/** Only identify supported public board formats; a platform name alone is not a feed. */
class CareerSourceDetector
{
    public function detect(Company $company): array
    {
        if ($company->ats_provider && $company->ats_provider !== 'official_discovery') {
            return ['provider'=>$company->ats_provider,'identifier'=>$company->ats_identifier,
                'jobs_feed_url'=>$company->jobs_feed_url,'confidence'=>'configured','source'=>'database'];
        }
        foreach (array_filter([$company->jobs_feed_url,$company->careers_url,$company->website]) as $url) {
            $result=$this->detectFromUrl($url);
            if ($result['provider']) return $result;
        }
        return $this->unknown();
    }

    public function detectFromUrl(string $url): array
    {
        $host=strtolower(parse_url($url,PHP_URL_HOST) ?? '');
        $path=parse_url($url,PHP_URL_PATH) ?? '';
        if (parse_url($url,PHP_URL_SCHEME)!=='https') return $this->unknown();
        $board=app(OfficialCareerDiscovery::class)->board($url,'');
        if ($board) return $this->result($board);
        if (preg_match('~^/wday/cxs/[^/]+/[^/]+/jobs$~',$path) && preg_match('/\.myworkdayjobs\.com$/',$host)) {
            return $this->result(['ats_provider'=>'workday','jobs_feed_url'=>$url]);
        }
        if (str_ends_with($host,'.oraclecloud.com') && preg_match('~/hcmUI/CandidateExperience/[^/]+/sites/([^/?]+)~i',$path,$m)) {
            return $this->result(['ats_provider'=>'oracle_recruiting','ats_identifier'=>$m[1],
                'jobs_feed_url'=>'https://'.$host.'/hcmRestApi/resources/latest/recruitingCEJobRequisitions']);
        }
        if ($host==='www.amazon.jobs' || $host==='amazon.jobs') {
            return $this->result(['ats_provider'=>'amazon','jobs_feed_url'=>'https://www.amazon.jobs/en/search.json']);
        }
        return $this->unknown();
    }

    public function detectFromHtml(string $html,string $url): array
    {
        $board=app(OfficialCareerDiscovery::class)->board($url,$html);
        if ($board) return $this->result($board);
        $dom=new \DOMDocument(); @$dom->loadHTML($html,LIBXML_NOERROR|LIBXML_NOWARNING);
        foreach ((new \DOMXPath($dom))->query('//a[@href]') as $anchor) {
            $result=$this->detectFromUrl(html_entity_decode($anchor->getAttribute('href')));
            if ($result['provider']) return $result;
        }
        return $this->unknown();
    }
    private function result(array $board): array
    {
        return ['provider'=>$board['ats_provider'],'identifier'=>$board['ats_identifier'] ?? null,
            'jobs_feed_url'=>$board['jobs_feed_url'] ?? null,'confidence'=>'high','source'=>'detector'];
    }
    private function unknown(): array
    {
        return ['provider'=>null,'identifier'=>null,'jobs_feed_url'=>null,'confidence'=>'unknown','source'=>'detector'];
    }
}
