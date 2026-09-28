<?php
namespace App\Services;

use App\Models\CareerTrack;
use App\Models\Company;
use App\Models\CompanyFacility;
use App\Models\IndustrialJob;
use App\Models\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CareerOpportunitySearch
{
    public function search(array $parameters): array
    {
        foreach (['factory_name'=>'company','factory'=>'company','sector'=>'industry','product'=>'products','total_employees'=>'employee_min'] as $alias=>$field) {
            if (!isset($parameters[$field]) && isset($parameters[$alias])) $parameters[$field]=$parameters[$alias];
        }
        $rules = [];
        foreach (['country','state','city','industrial_area','company','industry','products','business_unit','department','job_family','role','career_level','skills'] as $field) $rules[$field]='nullable|string|max:150';
        $filters = Validator::make($parameters, $rules+[
            'source_url'=>'nullable|url|max:1000','employee_min'=>'nullable|integer|min:0','employee_max'=>'nullable|integer|min:0',
            'experience'=>'nullable|numeric|min:0|max:60','salary_min'=>'nullable|numeric|min:0',
            'salary_currency'=>'nullable|in:INR,USD,EUR,GBP','page'=>'nullable|integer|min:1',
            'industrial_page'=>'nullable|integer|min:1','companies_page'=>'nullable|integer|min:1',
        ])->validate();
        $filters=array_filter($filters,fn($value)=>$value!==null && $value!=='');
        if (isset($filters['employee_min'],$filters['employee_max']) && $filters['employee_max']<$filters['employee_min']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['employee_max'=>'Maximum must be at least the minimum.']);
        }
        if (isset($filters['role'])) $filters['role']=str_ireplace(['metler','lab attendent','milling man'],['melter','lab attendant','milling operator'],$filters['role']);
        $facilities=CompanyFacility::query()->whereHas('company',fn($q)=>$q->active());
        foreach (['country','state','city','industrial_area','products'] as $field) {
            if (isset($filters[$field])) $facilities->where($field,'like',$this->like($filters[$field]));
        }
        if (isset($filters['industry'])) $this->industry($facilities, $filters['industry'], ['industry']);
        if (isset($filters['company'])) $facilities->whereHas('company',fn($q)=>$q->where('name','like',$this->like($filters['company'])));
        if (isset($filters['employee_min'])) $facilities->where('employee_min','>=',$filters['employee_min']);
        if (isset($filters['employee_max'])) $facilities->whereNotNull('employee_max')->where('employee_max','<=',$filters['employee_max']);
        $sourceCompany = null;
        if (isset($filters['source_url'])) {
            $host=strtolower((string)parse_url($filters['source_url'],PHP_URL_HOST));
            if (!in_array($host,['google.com','www.google.com'],true)) {
                $sourceCompany=Company::active()->where(fn($q)=>$q->where('website',rtrim($filters['source_url'],'/'))->orWhere('website',$filters['source_url'])->orWhere('careers_url',$filters['source_url'])->orWhere('jobs_feed_url',$filters['source_url']))->pluck('id');
                $facilities->whereIn('company_id',$sourceCompany);
            }
        }
        $jobs=Job::active()->with(['company','technologies']);
        app(JobGeography::class)->apply($jobs,array_intersect_key($filters,array_flip(['country','state','city'])));
        if ($sourceCompany!==null) $jobs->whereIn('company_id',$sourceCompany);
        if (isset($filters['company'])) $jobs->whereHas('company',fn($q)=>$q->where('name','like',$this->like($filters['company'])));
        if (isset($filters['industry'])) $jobs->whereHas('company',fn($q)=>$this->industry($q,$filters['industry']));
        if (isset($filters['industrial_area'])) $jobs->where('location','like',$this->like($filters['industrial_area']));
        if (isset($filters['products']) || isset($filters['employee_min']) || isset($filters['employee_max'])) {
            $jobs->whereIn('company_id',(clone $facilities)->select('company_id'));
        }
        foreach (['department'=>'department','business_unit'=>'source_payload->business_unit','job_family'=>'role_family'] as $field=>$column) if (isset($filters[$field])) $jobs->where($column,'like',$this->like($filters[$field]));
        if (isset($filters['role'])) $this->role($jobs,'title',$filters['role']);
        if (isset($filters['career_level'])) $this->level($jobs,'title',$filters['career_level']);
        if (isset($filters['skills'])) $jobs->search($filters['skills']);
        if (isset($filters['experience'])) $jobs->whereNotNull('experience_min')->where('experience_min','<=',$filters['experience'])->where(fn($q)=>$q->whereNull('experience_max')->orWhere('experience_max','>=',$filters['experience']));
        if (isset($filters['salary_min'])) $jobs->where('salary_currency',$filters['salary_currency']??'INR')->whereIn('salary_period',['annual','yearly','monthly'])->whereRaw("COALESCE(salary_max,salary_min) * CASE WHEN salary_period='monthly' THEN 12 ELSE 1 END >= ?",[$filters['salary_min']]);

        $industrial=IndustrialJob::live()->verified()->with(['company','area.state','department','role']);
        if (isset($filters['country']) && !in_array(strtolower($filters['country']),['india','in'],true)) $industrial->whereRaw('1=0');
        if (isset($filters['state'])) $industrial->whereHas('area.state',fn($q)=>$q->where('name','like',$this->like($filters['state'])));
        if (isset($filters['city'])) $industrial->whereHas('area',fn($q)=>$q->where('city','like',$this->like($filters['city'])));
        if (isset($filters['industrial_area'])) $industrial->whereHas('area',fn($q)=>$q->where('name','like',$this->like($filters['industrial_area'])));
        if (isset($filters['company'])) $industrial->whereHas('company',fn($q)=>$q->where('name','like',$this->like($filters['company'])));
        if (isset($filters['industry'])) $industrial->whereHas('company',fn($q)=>$this->industry($q,$filters['industry']));
        if (isset($filters['source_url']) && $sourceCompany!==null) $industrial->whereHas('company',fn($q)=>$q->where('website',$filters['source_url'])->orWhere('careers_url',$filters['source_url']));
        if (isset($filters['role'])) $this->role($industrial,'job_title',$filters['role']);
        if (isset($filters['career_level'])) $this->level($industrial,'job_title',$filters['career_level']);
        if (isset($filters['department'])) $industrial->whereHas('department',fn($q)=>$q->where('name','like',$this->like($filters['department'])));
        if (isset($filters['skills'])) app(IndustrialSearch::class)->jobs($industrial,app(IndustrialSearch::class)->tokens($filters['skills']));
        if (isset($filters['experience'])) $industrial->whereNotNull('experience_min')->where('experience_min','<=',$filters['experience'])->where(fn($q)=>$q->whereNull('experience_max')->orWhere('experience_max','>=',$filters['experience']));
        if (isset($filters['salary_min'])) {
            if (($filters['salary_currency']??'INR')!=='INR') $industrial->whereRaw('1=0');
            else $industrial->whereIn('salary_period',['annual','monthly'])->whereRaw("COALESCE(salary_max,salary_min) * CASE WHEN salary_period='monthly' THEN 12 ELSE 1 END >= ?",[$filters['salary_min']]);
        }
        // These attributes are not supplied by the legacy industrial feed. Do not silently ignore filters.
        if (isset($filters['products']) || isset($filters['employee_min']) || isset($filters['employee_max']) || isset($filters['business_unit']) || isset($filters['job_family'])) $industrial->whereRaw('1=0');
        $tracks=CareerTrack::with('roles')->when($filters['industry']??null,fn($q,$value)=>$q->where(fn($s)=>$this->industry($s,$value,['sector'])->orWhere('sector','Cross-sector')))
            ->when($filters['department']??null,fn($q,$value)=>$q->where('department','like',$this->like($value)))
            ->when($filters['role']??null,fn($q,$value)=>$q->whereHas('roles',fn($r)=>$r->where('name','like',$this->like($value))))
            ->orderBy('sector')->orderBy('department')->get();
        $benchmarks=DB::table('career_salary_benchmarks')
            ->when($filters['industry']??null,fn($q,$value)=>$this->industry($q,$value,['sector']))
            ->when($filters['role']??null,fn($q,$value)=>$q->where('role_name','like',$this->like($value)))->limit(80)->get();
        return [
            'filters'=>$filters,
            'jobs'=>$jobs->latest('posted_at')->paginate(20,['*'],'page',(int)($filters['page']??1))->withQueryString(),
            'industrialJobs'=>$industrial->latest('id')->paginate(12,['*'],'industrial_page',(int)($filters['industrial_page']??1))->withQueryString(),
            'facilities'=>$facilities->with('company')->orderBy('name')->paginate(12,['*'],'companies_page',(int)($filters['companies_page']??1))->withQueryString(),
            'tracks'=>$tracks,'benchmarks'=>$benchmarks,
            'discovery_url'=>'https://www.google.com/search?'.http_build_query(['q'=>implode(' ',array_intersect_key($filters,array_flip(['country','state','city','industrial_area','industry','company','products','role']))).' official careers']),
        ];
    }

    private function industry($query, string $value, array $columns=['industry','sector'])
    {
        $terms=match(strtolower(trim($value))) {
            'software & it','software','it' => ['Software','IT Services','Technology','Cloud','Internet','Cybersecurity'],
            'automobile','automotive' => ['Automobile','Automotive','Forging'],
            'casting','foundry','casting industry' => ['Casting','Foundry','Forging'],
            'hotel & tourism','travel & hospitality' => ['Hotel','Tourism','Travel','Hospitality'],
            'ca & accounting','accounting' => ['Accounting','Audit'],
            default => [$value],
        };
        return $query->where(function($q) use($terms,$columns) {
            foreach($columns as $column) foreach($terms as $term) $q->orWhere($column,'like',$this->like($term));
        });
    }

    private function role($query, string $column, string $value): void
    {
        if (preg_match('/^sde\\s*([123])$/i',trim($value),$match)) {
            $grades=['1'=>['SDE 1','SDE1','Software Engineer I','Software Engineer 1'],'2'=>['SDE 2','SDE2','Software Engineer II','Software Engineer 2'],'3'=>['SDE 3','SDE3','Software Engineer III','Software Engineer 3']];
            $pattern='(^|[^a-z0-9])('.implode('|',array_map(fn($term)=>preg_quote(strtolower($term),'~'),$grades[$match[1]])).')($|[^a-z0-9])';
            $operator=$query->getConnection()->getDriverName()==='pgsql'?'~*':'REGEXP';
            $query->whereRaw('LOWER('.$column.') '.$operator.' ?',[$pattern]);
            return;
        }
        foreach(preg_split('/\\s+/',trim($value)) as $word) $query->where($column,'like',$this->like($word));
    }

    private function alternatives($query,string $column,array $terms): void
    {
        foreach($terms as $term) $query->orWhere($column,'like',$this->like($term));
    }

    private function level($query,string $column,string $value): void
    {
        $terms=match(strtolower($value)) {
            'entry'=>['Junior','Trainee','Associate','Graduate','Entry'],
            'experienced'=>['Engineer','Developer','Specialist','Analyst','Operator'],
            'lead'=>['Lead','Principal','Architect'],
            'executive'=>['Chief','CEO','CTO','CFO','CIO','Vice President','VP '],
            default=>[$value],
        };
        $query->where(fn($q)=>$this->alternatives($q,$column,$terms));
    }

    private function like(string $value): string { return '%'.addcslashes(trim($value), '%_\\').'%'; }

    public function refreshSource(string $url): array
    {
        $company=Company::active()->where('sync_enabled',true)->where(fn($q)=>$q->where('website',$url)->orWhere('careers_url',$url)->orWhere('jobs_feed_url',$url))->first();
        if (!$company) throw new \InvalidArgumentException('Use a registered official career/feed URL. Google is a discovery destination, not a vacancies feed.');
        return app(JobScraper::class)->scrapeCompany($company);
    }
}
