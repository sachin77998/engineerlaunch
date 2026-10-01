<?php
namespace App\Services;

use App\Models\Company;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;

/** Public employer feeds requested in the company coverage requirements. */
trait RequestedCareerFeeds
{
    protected function scrapeBoundedOfficialDiscovery(Company $company): array
    {
        $scan=app(OfficialCareerDiscovery::class)->scan($company);
        $jobs=[];
        foreach ($scan['jobs'] as $job) $jobs[$job['external_url']]=$this->normalizeJob($company,$job);
        foreach (array_slice($scan['boards'],0,3) as $board) {
            $candidate=clone $company;
            $candidate->forceFill(['jobs_feed_url'=>null,'ats_identifier'=>null]);
            $candidate->forceFill($board);
            try {
                foreach ($this->scrapeCompanyJobs($candidate) as $job) $jobs[$job['external_url']]=$job;
            } catch (\Throwable $e) {
                $scan['errors'][]='Linked '.$board['ats_provider'].' feed failed: '.$e->getMessage();
            }
        }
        // Bounded discovery never establishes that all employer pages were exhausted.
        $this->completeFeed=false;
        if (!$jobs) throw new \RuntimeException('No importable vacancies found after checking '.$scan['pages'].' official pages. '.implode('; ',$scan['errors']).' Source needs review; existing jobs retained.');
        return array_values($jobs);
    }


    protected function scrapeSuccessFactorsWidget(Company $company, string $html, string $origin): \Generator
    {
        if (!preg_match('/CSRFToken\s*=\s*[\'"]([^\'"]+)[\'"]/', $html, $token)) {
            throw new \RuntimeException('Public careers search token was not found.');
        }
        $key='career-widget-page:'.$company->id;
        $page=(int)\Illuminate\Support\Facades\Cache::get($key,0);
        if ($page===0) \Illuminate\Support\Facades\Cache::forget($key.':incomplete');
        elseif (\Illuminate\Support\Facades\Cache::get($key.':incomplete')) $this->completeFeed=false;
        // A resumed scan contains only part of the catalogue; never retire other pages.
        $this->allowRetirement=false;
        $deadline=microtime(true)+120;
        for (; $page<10000; $page++) {
            $response=$this->httpClient->post($origin.'/services/recruiting/v1/jobs',[
                'headers'=>['X-CSRF-Token'=>$token[1],'Referer'=>$origin.'/search/?q='],
                'json'=>['locale'=>'en_US','pageNumber'=>$page,'sortBy'=>'','keywords'=>'','location'=>'','facetFilters'=>(object)[], 'brand'=>'','skills'=>[],'categoryId'=>0],
            ]);
            $data=json_decode((string)$response->getBody(),true,512,JSON_THROW_ON_ERROR);
            if (!isset($data['jobSearchResult'],$data['totalJobs'])) throw new \RuntimeException('Unrecognized public careers search response.');
            $rows=$data['jobSearchResult']; $jobs=[];
            foreach ($rows as $item) {
                $r=$item['response'] ?? [];
                if (empty($r['id']) || empty($r['unifiedStandardTitle'])) { $this->completeFeed=false; continue; }
                $slug=$r['unifiedUrlTitle'] ?? $r['urlTitle'] ?? '';
                $url=$origin.'/job/'.rawurlencode(rawurldecode($slug)).'/'.rawurlencode($r['id']).'-en_US';
                $locations=$r['jobLocationShort'] ?? array_merge((array)($r['custprimecity'] ?? []),(array)($r['custCountryRegion'] ?? []));
                $jobs[$url]=['title'=>$r['unifiedStandardTitle'],'description'=>'','external_url'=>$url,
                    'location'=>strip_tags(implode(', ',$locations)) ?: 'Not specified',
                    'source_payload'=>['requisition_id'=>$r['id'],'career_page_url'=>$company->careers_url]];
                if (!empty($r['unifiedStandardStart'])) {
                    try { $jobs[$url]['posted_at']=\Carbon\Carbon::createFromFormat('n/j/y',$r['unifiedStandardStart'])->startOfDay(); } catch (\Throwable $e) {}
                }
            }
            foreach ($this->withHtmlDescriptions($company,$jobs,'//*[@itemprop="description" or @data-careersite-propertyid="description"]') as $job) yield $job;
            if (!$rows || ($page+1)*10 >= (int)$data['totalJobs']) {
                \Illuminate\Support\Facades\Cache::forget($key);
                \Illuminate\Support\Facades\Cache::forget($key.':incomplete');
                return;
            }
            // Persist progress only after the caller has saved this page's jobs.
            \Illuminate\Support\Facades\Cache::forever($key,$page+1);
            if (!$this->completeFeed) \Illuminate\Support\Facades\Cache::forever($key.':incomplete',true);
            if (microtime(true)>$deadline) { $this->continueFeed=true; return; }
        }
        $this->completeFeed=false;
    }

    protected function scrapeBebo(Company $company): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($this->getContent($company->careers_url), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($dom);
        $jobs = [];
        foreach ($xpath->query('//*[contains(concat(" ",normalize-space(@class)," ")," single-job ")]') as $node) {
            $title = trim($xpath->evaluate('string(.//h5[1])', $node));
            $description = trim($xpath->evaluate('string(.//*[contains(@class,"job-description-wrapper")][1])', $node));
            $url = trim($xpath->evaluate('string(.//*[@data-microsite][1]/@data-microsite)', $node));
            if (!$url) $url = trim($xpath->evaluate('string(.//*[@data-redirect][1]/@data-redirect)', $node));
            if (!$url) $url = trim($xpath->evaluate('string(.//a[contains(@class,"apply-now")][1]/@href)', $node));
            if (!$title || !filter_var($url,FILTER_VALIDATE_URL) || !str_ends_with(parse_url($url,PHP_URL_HOST) ?? '', '.mobile-recruit.com')) continue;
            $india = $xpath->query('ancestor::*[contains(@class,"India_Careers")]', $node)->length > 0;
            $mexico = $xpath->query('ancestor::*[contains(@class,"Mexico_Careers")]', $node)->length > 0;
            $experience = trim($xpath->evaluate('string(.//*[contains(@class,"job-subtitle")][1])', $node));
            preg_match('/(\d+)\s*-\s*(\d+)\s*Years/i', $experience, $years);
            $jobs[$url] = $this->normalizeJob($company, [
                'title'=>$title, 'description'=>$description, 'external_url'=>$url,
                'location'=>$india ? 'Chandigarh, India' : ($mexico ? 'Mexico' : 'Not specified'),
                'experience_min'=>isset($years[1]) ? (int)$years[1] : null,
                'experience_max'=>isset($years[2]) ? (int)$years[2] : null,
                'source_payload'=>['career_page_url'=>$company->careers_url],
            ]);
        }
        return array_values($jobs);
    }

    protected function scrapeInfosys(Company $company): array
    {
        $html = $this->getContent($company->careers_url);
        if (!preg_match('~<script[^>]+src=["\'](https://[^"\']+/merged/js/[^"\']+)~i', $html, $script)) throw new \RuntimeException('Infosys public search script not found.');
        $js = $this->getContent(html_entity_decode($script[1]));
        if (!preg_match('/algoliasearch\([\'"]([A-Za-z0-9]+)[\'"],\s*[\'"]([A-Za-z0-9]+)[\'"]/', $js, $credentials) ||
            !preg_match('/indexName:\s*[\'"]([A-Za-z0-9_-]+)[\'"]/', $js, $index)) throw new \RuntimeException('Infosys public search configuration changed.');
        $endpoint = 'https://'.strtolower($credentials[1]).'-dsn.algolia.net/1/indexes/'.$index[1].'/query';
        $jobs = []; $expected = null;
        for ($page=0; $page<200; $page++) {
            $response = $this->httpClient->post($endpoint, [
                'headers'=>['X-Algolia-Application-Id'=>$credentials[1], 'X-Algolia-API-Key'=>$credentials[2]],
                'json'=>['query'=>'','hitsPerPage'=>100,'page'=>$page],
            ]);
            $payload = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $expected ??= (int)($payload['nbHits'] ?? 0);
            foreach ($payload['hits'] ?? [] as $row) {
                $url = (array)($row['redirect_url'] ?? []);
                $url = $url[0] ?? '';
                if (parse_url($url,PHP_URL_HOST) !== 'digitalcareers.infosys.com' || empty($row['title'])) continue;
                $jobs[$url] = [
                    'title'=>$row['title'], 'description'=>'', 'external_url'=>$url,
                    'location'=>implode(', ',array_merge((array)($row['work_location'] ?? []),(array)($row['country'] ?? []))),
                    'source_payload'=>['requisition_id'=>$row['reqid'] ?? [],'career_page_url'=>$company->careers_url],
                ];
            }
            if ($page+1 >= (int)($payload['nbPages'] ?? 1) || empty($payload['hits'])) break;
        }
        if (count($jobs) !== $expected) $this->completeFeed = false;
        return $this->withHtmlDescriptions($company, $jobs, '//*[contains(concat(" ",normalize-space(@class)," ")," description-page-right ")]');
    }

    protected function scrapeOracleRecruiting(Company $company): array
    {
        $endpoint = rtrim($company->jobs_feed_url, '/');
        $site = $company->ats_identifier ?: 'CX_1';
        $jobs = []; $expected = null;
        for ($offset=0; $offset<20000; $offset+=100) {
            $url = $endpoint.'?'.http_build_query(['onlyData'=>'true','expand'=>'requisitionList','finder'=>"findReqs;siteNumber={$site},limit=100,offset={$offset}"]);
            $payload = json_decode($this->getContent($url), true, 512, JSON_THROW_ON_ERROR);
            $page = $payload['items'][0] ?? [];
            $expected ??= (int)($page['TotalJobsCount'] ?? 0);
            $rows = $page['requisitionList'] ?? [];
            foreach ($rows as $row) {
                if (empty($row['Id']) || empty($row['Title'])) continue;
                $jobUrl = rtrim($company->careers_url, '/').'/job/'.rawurlencode($row['Id']);
                $jobs[$row['Id']] = [
                    'title'=>$row['Title'], 'description'=>strip_tags($row['ShortDescriptionStr'] ?? ''),
                    'external_url'=>$jobUrl, 'location'=>$row['PrimaryLocation'] ?? 'Not specified',
                    'posted_at'=>$row['PostedDate'] ?? null, 'expires_at'=>$row['PostingEndDate'] ?? null,
                    'source_payload'=>['requisition_id'=>$row['Id'],'career_page_url'=>$company->careers_url],
                ];
            }
            if (!$rows || $offset+count($rows) >= $expected) break;
        }
        if (count($jobs) !== $expected) $this->completeFeed = false;
        $detailEndpoint = str_replace('recruitingCEJobRequisitions','recruitingCEJobRequisitionDetails',$endpoint);
        $requests = function () use ($jobs,$detailEndpoint,$site) {
            foreach ($jobs as $id=>$job) yield $id => new Request('GET', $detailEndpoint.'?'.http_build_query([
                'onlyData'=>'true','expand'=>'all','finder'=>'ById;Id="'.$id.'",siteNumber='.$site,
            ]));
        };
        $pool = new Pool($this->httpClient, $requests(), [
            'concurrency'=>3,
            'fulfilled'=>function ($response,$id) use (&$jobs) {
                $data = json_decode((string)$response->getBody(), true);
                $row = $data['items'][0] ?? [];
                if (empty($row['ExternalDescriptionStr'])) { $this->completeFeed=false; $jobs[$id]['description_complete']=false; return; }
                $jobs[$id]['description'] = html_entity_decode(strip_tags(implode(' ',[
                    $row['ExternalDescriptionStr'], $row['ExternalQualificationsStr'] ?? '', $row['ExternalResponsibilitiesStr'] ?? '',
                ])));
                $jobs[$id]['location'] = $row['PrimaryLocation'] ?? $jobs[$id]['location'];
                $jobs[$id]['job_type'] = $row['JobSchedule'] ?? null;
                $jobs[$id]['source_payload']['business_unit'] = $row['BusinessUnit'] ?? null;
            },
            'rejected'=>function ($reason,$id) use (&$jobs) { $this->completeFeed=false; $jobs[$id]['description_complete']=false; },
        ]);
        $pool->promise()->wait();
        return array_values(array_map(fn($job)=>$this->normalizeJob($company,$job),$jobs));
    }

    protected function withHtmlDescriptions(Company $company, array $jobs, string $selector): array
    {
        $requests = function () use ($jobs) {
            foreach ($jobs as $url=>$job) yield $url => new Request('GET',$url);
        };
        $pool = new Pool($this->httpClient, $requests(), [
            'concurrency'=>3,
            'fulfilled'=>function ($response,$url) use (&$jobs,$selector) {
                $dom = new \DOMDocument();
                @$dom->loadHTML((string)$response->getBody(), LIBXML_NOERROR | LIBXML_NOWARNING);
                $xpath = new \DOMXPath($dom);
                $nodes = $xpath->query($selector);
                $text = [];
                foreach ($nodes as $node) $text[] = trim($node->textContent);
                $description = trim(implode("\n",$text));
                if ($description === '') { $this->completeFeed=false; $jobs[$url]['description_complete']=false; return; }
                $jobs[$url]['description'] = $description;
                $jobs[$url]['description_complete'] = true;
            },
            'rejected'=>function ($reason,$url) use (&$jobs) { $this->completeFeed=false; $jobs[$url]['description_complete']=false; },
        ]);
        $pool->promise()->wait();
        return array_values(array_map(fn($job)=>$this->normalizeJob($company,$job),$jobs));
    }
}
