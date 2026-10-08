<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyCategory;
use App\Models\CompanyFacility;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\Skill;
use App\Services\SectorCatalogImporter;
use App\Services\SectorDirectory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Conversational company-profile builder and job-posting assistant for employers. */
class EmployerAssistantController extends Controller
{
    private const JOB_TYPES = ['Full Time', 'Part Time', 'Contract', 'Internship', 'Apprenticeship', 'Trainee', 'Temporary'];

    private function steps(string $flow): array
    {
        return $flow === 'company' ? [
            'name' => ['What is your company name?', 'text', 'required|string|max:150'],
            'company_type' => ['What kind of organisation are you?', 'select', 'required|string'],
            'sector' => ['Which sector does your company belong to?', 'select', 'required|string'],
            'subsector' => ['Which category fits best within that sector?', 'select', 'nullable|string'],
            'website' => ['What is your official website? (optional)', 'url', 'nullable|url|max:255'],
            'country' => ['Which country is your unit in?', 'text', 'required|string|max:100'],
            'state' => ['Which state?', 'text', 'nullable|string|max:100'],
            'city' => ['Which city?', 'text', 'nullable|string|max:100'],
            'industrial_area' => ['Which industrial area or estate is the factory/office in? (e.g. SIDCUL Haridwar Sector 11, IMT Manesar)', 'text', 'nullable|string|max:150'],
            'employee_count' => ['How many employees work there?', 'number', 'nullable|integer|min:1|max:5000000'],
            'products' => ['What does your company manufacture or provide?', 'textarea', 'nullable|string|max:1000'],
            'brands' => ['List your brand names, separated by commas (optional).', 'text', 'nullable|string|max:500'],
            'designation' => ['What is your designation?', 'text', 'nullable|string|max:100'],
            'phone' => ['What is your contact phone number?', 'tel', 'nullable|string|max:30'],
        ] : [
            'title' => ['Which position are you hiring for?', 'text', 'required|string|max:255'],
            'category' => ['Which department is this role in?', 'select', 'required|string|max:100'],
            'job_type' => ['What type of employment is it?', 'select', 'required|string'],
            'work_mode' => ['Where will the person work?', 'select', 'required|in:On-site,Remote,Hybrid'],
            'vacancies' => ['How many people do you need?', 'number', 'required|integer|min:1|max:10000'],
            'experience_min' => ['Minimum experience required (years)?', 'number', 'nullable|integer|min:0|max:60'],
            'experience_max' => ['Maximum experience (years)?', 'number', 'nullable|integer|min:0|max:60'],
            'education' => ['Required qualification? (e.g. ITI Fitter, Diploma Mechanical, B.Tech CSE)', 'text', 'nullable|string|max:100'],
            'skills' => ['Key skills, separated by commas.', 'text', 'nullable|string|max:2000'],
            'salary_min' => ['Minimum annual salary in INR? (leave blank if undisclosed)', 'number', 'nullable|numeric|min:0'],
            'salary_max' => ['Maximum annual salary in INR?', 'number', 'nullable|numeric|min:0'],
            'state' => ['Which state is the job in?', 'text', 'nullable|string|max:100'],
            'location' => ['Which city or plant location?', 'text', 'nullable|string|max:150'],
            'description' => ['Describe the responsibilities in a few lines.', 'textarea', 'required|string|max:10000'],
            'action' => ['Should I publish it now or save a draft?', 'select', 'required|in:publish,draft'],
        ];
    }

    public function show(Request $r, string $flow)
    {
        $profile = $this->guard($r, $flow);
        [$answers, $index] = $this->state($flow);
        $steps = $this->steps($flow);
        $keys = array_keys($steps);
        $review = $index >= count($keys);
        $current = $review ? null : $keys[$index];
        $display = [];
        foreach (array_slice($keys, 0, $index) as $key) {
            if ($steps[$key][1] === 'select' && filled($answers[$key] ?? null)) $display[$key] = $this->options($flow, $key, $answers, $profile)[$answers[$key]] ?? $answers[$key];
        }
        return view('employer.assistant', [
            'flow' => $flow, 'steps' => $steps, 'keys' => $keys, 'index' => $index, 'answers' => $answers, 'review' => $review,
            'current' => $current, 'options' => $current ? $this->options($flow, $current, $answers, $profile) : [],
            'suggestions' => $current ? $this->suggestions($flow, $current, $profile) : [],
            'default' => $current ? $this->defaultAnswer($flow, $current, $profile) : null, 'profile' => $profile, 'display' => $display,
        ]);
    }

    public function answer(Request $r, string $flow)
    {
        $profile = $this->guard($r, $flow);
        [$answers, $index] = $this->state($flow);
        $steps = $this->steps($flow);
        $keys = array_keys($steps);
        abort_if($index >= count($keys), 422);
        $key = $keys[$index];
        $value = $r->validate(['answer' => $steps[$key][2]])['answer'] ?? null;
        if ($steps[$key][1] === 'select' && filled($value)) {
            $options = $this->options($flow, $key, $answers, $profile);
            if ($options && !array_key_exists($value, $options)) return back()->withErrors(['answer' => 'Please choose one of the options.']);
        }
        if ($key === 'experience_max' && filled($value) && filled($answers['experience_min'] ?? null) && $value < $answers['experience_min']) {
            return back()->withErrors(['answer' => 'Maximum experience must be at least the minimum.']);
        }
        if ($key === 'salary_max' && filled($value) && filled($answers['salary_min'] ?? null) && $value < $answers['salary_min']) {
            return back()->withErrors(['answer' => 'Maximum salary must be at least the minimum.']);
        }
        $answers[$key] = $value;
        // A sector without subsectors has nothing to ask next.
        if ($key === 'sector' && !$this->options($flow, 'subsector', $answers, $profile)) { $answers['subsector'] = null; $index++; }
        session(["employer_assistant.$flow" => ['answers' => $answers, 'index' => $index + 1]]);
        return redirect()->route('employer.assistant', $flow);
    }

    public function back(Request $r, string $flow)
    {
        $this->guard($r, $flow);
        [$answers, $index] = $this->state($flow);
        session(["employer_assistant.$flow" => ['answers' => $answers, 'index' => max(0, $index - 1)]]);
        return redirect()->route('employer.assistant', $flow);
    }

    public function reset(Request $r, string $flow)
    {
        $this->guard($r, $flow);
        session()->forget("employer_assistant.$flow");
        return redirect()->route('employer.assistant', $flow);
    }

    public function complete(Request $r, string $flow)
    {
        $profile = $this->guard($r, $flow);
        [$answers, $index] = $this->state($flow);
        abort_if($index < count($this->steps($flow)), 422, 'Answer all assistant questions first.');
        if ($flow === 'company') {
            $this->saveCompany($r, $answers, $profile);
            $message = 'Company profile saved. Next, post your first job with the assistant.';
            $redirect = redirect()->route('employer.assistant', 'job');
        } else {
            $job = $this->saveJob($r, $answers, $profile);
            $message = $job->status === 'published' ? 'Job published successfully.' : 'Draft saved. You can edit it any time.';
            $redirect = redirect()->route('employer.dashboard');
        }
        session()->forget("employer_assistant.$flow");
        return $redirect->with('success', $message);
    }

    private function saveCompany(Request $r, array $a, ?EmployerProfile $profile): void
    {
        DB::transaction(function () use ($r, $a, $profile) {
            $company = $profile?->company ?? Company::firstOrNew(['slug' => Str::slug($a['name'])], ['name' => $a['name']]);
            $sector = $this->sectorBySlug($a['sector'] ?? null);
            $subsector = $sector?->children->firstWhere('slug', $a['subsector'] ?? null);
            $brands = collect(explode(',', (string) ($a['brands'] ?? '')))->map(fn ($b) => trim($b))->filter()->unique()->values()->all();
            $company->forceFill([
                'name' => $a['name'], 'company_type' => $a['company_type'], 'website' => $a['website'] ?? $company->website,
                'country' => $a['country'], 'headquarters' => $a['city'] ?? $company->headquarters,
                'industry' => $sector?->name ?? $company->industry, 'sector' => $subsector?->name ?? $company->sector,
                'employee_count' => $a['employee_count'] ?? $company->employee_count, 'products' => $a['products'] ?? $company->products,
                'brands' => array_values(array_unique(array_merge($company->brands ?? [], $brands))) ?: null, 'is_active' => true,
            ])->save();
            $company->categories()->syncWithoutDetaching(array_filter([$sector?->id, $subsector?->id]));
            if (filled($a['city'] ?? null) || filled($a['industrial_area'] ?? null)) {
                CompanyFacility::updateOrCreate(['slug' => Str::limit(Str::slug($company->slug . ' ' . ($a['city'] ?? '') . ' ' . ($a['industrial_area'] ?? '')), 180, '')], [
                    'company_id' => $company->id, 'name' => $company->name . ' - ' . ($a['industrial_area'] ?: $a['city']),
                    'facility_type' => 'Employer-declared unit', 'country' => $a['country'], 'state' => $a['state'] ?? null,
                    'city' => $a['city'] ?? null, 'industrial_area' => $a['industrial_area'] ?? null, 'industry' => $sector?->name ?? 'General',
                    'products' => $a['products'] ?? null, 'employee_min' => $a['employee_count'] ?? null, 'employee_max' => $a['employee_count'] ?? null,
                    'source_url' => $a['website'] ?? route('companies.show', $company->slug), 'verified_on' => now()->toDateString(),
                ]);
            }
            EmployerProfile::updateOrCreate(['user_id' => $r->user()->id], ['company_id' => $company->id,
                'designation' => $a['designation'] ?? $profile?->designation, 'phone' => $a['phone'] ?? $profile?->phone]);
        });
        SectorDirectory::flush();
    }

    private function saveJob(Request $r, array $a, EmployerProfile $profile): Job
    {
        return DB::transaction(function () use ($r, $a, $profile) {
            $publish = $a['action'] === 'publish';
            $job = Job::create([
                'company_id' => $profile->company_id, 'employer_id' => $r->user()->id, 'title' => $a['title'], 'role' => $a['title'],
                'category' => $a['category'], 'department' => Str::limit($a['category'], 100, ''), 'job_type' => $a['job_type'], 'work_mode' => $a['work_mode'],
                'vacancies' => $a['vacancies'], 'experience_min' => $a['experience_min'] ?? null, 'experience_max' => $a['experience_max'] ?? null,
                'education' => $a['education'] ?? null, 'description' => $a['description'], 'country' => $profile->company?->country ?: 'India',
                'state' => $a['state'] ?? null, 'location' => $a['location'] ?? null,
                'salary_min' => $a['salary_min'] ?? null, 'salary_max' => $a['salary_max'] ?? null, 'salary_currency' => 'INR', 'salary_period' => 'annual',
                'salary_type' => filled($a['salary_min'] ?? null) ? 'range' : 'undisclosed', 'application_method' => 'portal',
                'slug' => Str::slug($a['title']) . '-' . Str::lower(Str::random(7)), 'status' => $publish ? 'published' : 'draft',
                'job_visibility' => $publish ? 'public' : 'draft', 'posting_source' => 'official_company', 'source' => 'employer',
                'is_active' => $publish, 'posted_at' => $publish ? now() : null, 'published_at' => $publish ? now() : null,
            ]);
            foreach (collect(preg_split('/[,\n]+/', (string) ($a['skills'] ?? '')))->map(fn ($s) => trim($s))->filter()->unique()->take(50) as $name) {
                $skill = Skill::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
                $job->skills()->attach($skill->id, ['importance' => 'required']);
            }
            $job->locations()->create(['country' => $job->country, 'state' => $job->state, 'city' => $job->location]);
            return $job;
        });
    }

    private function options(string $flow, string $key, array $answers, ?EmployerProfile $profile): array
    {
        return match ("$flow.$key") {
            'company.company_type' => ['company' => 'Company / Manufacturer', 'recruitment_agency' => 'Recruitment agency', 'consultancy' => 'Consultancy', 'staffing_agency' => 'Staffing agency'],
            'company.sector' => app(SectorDirectory::class)->tree()->pluck('name', 'slug')->all(),
            'company.subsector' => $this->sectorBySlug($answers['sector'] ?? null)?->children->pluck('name', 'slug')->all() ?? [],
            'job.category' => collect(array_keys($this->companySector($profile)?->roles ?? []))
                ->merge(['Production', 'Quality', 'Maintenance', 'Engineering', 'Sales', 'Human Resources', 'Finance & Accounts', 'Supply Chain', 'Other'])
                ->unique()->mapWithKeys(fn ($d) => [$d => $d])->all(),
            'job.job_type' => array_combine(self::JOB_TYPES, self::JOB_TYPES),
            'job.work_mode' => ['On-site' => 'On-site', 'Hybrid' => 'Hybrid', 'Remote' => 'Remote'],
            'job.action' => ['publish' => 'Publish now', 'draft' => 'Save as draft'],
            default => [],
        };
    }

    /** Role names from the company's sector catalog, offered while typing the job title. */
    private function suggestions(string $flow, string $key, ?EmployerProfile $profile): array
    {
        if ("$flow.$key" !== 'job.title') return [];
        return collect($this->companySector($profile)?->roles ?? [])->flatten()->unique()->values()->all();
    }

    private function defaultAnswer(string $flow, string $key, ?EmployerProfile $profile): ?string
    {
        $company = $profile?->company;
        $facility = $company?->facilities()->latest('id')->first();
        return match ("$flow.$key") {
            'company.name' => $company?->name, 'company.website' => $company?->website, 'company.country' => $company?->country ?: 'India',
            'company.designation' => $profile?->designation, 'company.phone' => $profile?->phone,
            'job.state' => $facility?->state, 'job.location' => $facility?->city,
            default => null,
        };
    }

    private function companySector(?EmployerProfile $profile): ?CompanyCategory
    {
        $parentId = $profile?->company?->categories()->where('taxonomy', SectorCatalogImporter::TAXONOMY)->whereNotNull('parent_id')->value('parent_id');
        $sector = $parentId ? CompanyCategory::find($parentId) : $profile?->company?->categories()->where('taxonomy', SectorCatalogImporter::TAXONOMY)->whereNull('parent_id')->first();
        return $sector;
    }

    private function sectorBySlug(?string $slug): ?CompanyCategory
    {
        return $slug ? app(SectorDirectory::class)->find($slug) : null;
    }

    private function state(string $flow): array
    {
        $state = session("employer_assistant.$flow", []);
        return [$state['answers'] ?? [], (int) ($state['index'] ?? 0)];
    }

    private function guard(Request $r, string $flow): ?EmployerProfile
    {
        abort_unless(in_array($flow, ['company', 'job'], true), 404);
        $profile = $r->user()->employerProfile()->with('company')->first();
        if ($flow === 'job' && !$profile) abort(redirect()->route('employer.assistant', 'company')->withErrors(['answer' => 'Let us build your company profile first.']));
        return $profile;
    }
}
