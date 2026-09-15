<?php

namespace App\Http\Controllers;

use App\Models\InterviewCompany;
use App\Models\InterviewExperience;
use App\Models\InterviewRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CompanyExperienceController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['search' => 'nullable|string|max:100', 'tier' => ['nullable', Rule::in(['tier_1', 'tier_2', 'tier_3'])], 'industry' => 'nullable|string|max:100']);
        $query = InterviewCompany::where('is_active', true)->withCount(['experiences as published_experiences_count' => fn($q) => $q->where('is_published', true)]);
        if ($search = trim($data['search'] ?? '')) $query->where('name', 'like', '%' . $search . '%');
        if (!empty($data['tier'])) $query->where('tier', $data['tier']);
        if (!empty($data['industry'])) $query->where('industry', $data['industry']);
        $companies = $query->orderByDesc('is_featured')->orderBy('name')->paginate(24)->withQueryString();
        $industries = InterviewCompany::where('is_active', true)->whereNotNull('industry')->distinct()->orderBy('industry')->pluck('industry');
        return view('company-experiences.index', compact('companies', 'industries'));
    }

    public function company(Request $request, string $slug)
    {
        $company = InterviewCompany::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $query = $company->experiences()->where('is_published', true)->with(['role', 'rounds', 'package']);
        if ($request->filled('role')) $query->where('role_id', $request->integer('role'));
        if ($request->filled('level')) $query->where('experience_level', (string)$request->string('level'));
        if ($request->filled('location')) $query->where('location', 'like', '%' . trim((string)$request->string('location')) . '%');
        $experiences = $query->latest()->paginate(10)->withQueryString();
        $roles = $company->roles()->where('is_active', true)->orderBy('role_name')->get();
        $topicCounts = $company->experiences()->where('is_published', true)->with('rounds:id,experience_id,topics')->get()->flatMap->rounds->flatMap(fn($r) => $r->topics ?? [])->countBy()->sortDesc()->take(12);
        $packages = $company->packages()->where('is_verified', true)->with('role')->latest()->take(12)->get();
        return view('company-experiences.company', compact('company', 'experiences', 'roles', 'topicCounts', 'packages'));
    }

    public function experience(string $companySlug, int $experienceId)
    {
        $experience = InterviewExperience::whereKey($experienceId)->where('is_published', true)->whereHas('company', fn($q) => $q->where('slug', $companySlug)->where('is_active', true))->with(['company', 'role', 'rounds', 'package'])->firstOrFail();
        return view('company-experiences.experience', ['company' => $experience->company, 'experience' => $experience]);
    }

    public function create(Request $request)
    {
        $companies = InterviewCompany::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return view('company-experiences.create', ['companies' => $companies, 'selectedCompany' => $request->integer('company')]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:interview_companies,id',
            'role_name' => 'required|string|max:120',
            'department' => 'nullable|string|max:120',
            'experience_level' => 'required|string|max:60',
            'location' => 'required|string|max:120',
            'interview_date' => 'nullable|date|before_or_equal:today',
            'application_source' => 'nullable|string|max:120',
            'result' => ['required', Rule::in(['selected', 'rejected', 'offer', 'waiting', 'withdrew'])],
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard', 'very_hard'])],
            'duration_days' => 'nullable|integer|min:0|max:730',
            'overall_experience' => 'required|string|min:40|max:10000',
            'preparation_advice' => 'nullable|string|max:6000',
            'rounds' => 'required|array|min:1|max:12',
            'rounds.*.round_name' => 'required|string|max:120',
            'rounds.*.round_type' => ['required', Rule::in(['recruiter', 'aptitude', 'coding', 'dsa', 'system_design', 'machine_coding', 'project', 'managerial', 'behavioral', 'hr', 'technical', 'role_specific'])],
            'rounds.*.duration_minutes' => 'nullable|integer|min:1|max:600',
            'rounds.*.difficulty' => ['nullable', Rule::in(['easy', 'medium', 'hard', 'very_hard'])],
            'rounds.*.description' => 'required|string|max:5000',
            'rounds.*.topics' => 'nullable|string|max:2000',
            'rounds.*.questions' => 'nullable|string|max:5000',
            'rounds.*.candidate_experience' => 'nullable|string|max:3000',
            'currency' => ['nullable', Rule::in(['INR', 'USD', 'EUR', 'GBP', 'AED', 'SGD', 'CAD', 'AUD'])],
            'experience_years' => 'nullable|numeric|min:0|max:60',
            'base_salary' => 'nullable|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'stock_value' => 'nullable|numeric|min:0',
            'total_compensation' => 'nullable|numeric|min:0',
            'salary_notes' => 'nullable|string|max:1000',
        ]);
        $company = InterviewCompany::whereKey($data['company_id'])->where('is_active', true)->firstOrFail();
        DB::transaction(function () use ($data, $company, $request) {
            $role = InterviewRole::firstOrCreate(['company_id' => $company->id, 'role_name' => $data['role_name'], 'experience_level' => $data['experience_level']], ['department' => $data['department'] ?? null, 'is_active' => true]);
            $experience = InterviewExperience::create(['company_id' => $company->id, 'role_id' => $role->id, 'user_id' => $request->user()->id, 'candidate_name' => $request->user()->name, 'experience_level' => $data['experience_level'], 'location' => $data['location'], 'interview_date' => $data['interview_date'] ?? null, 'application_source' => $data['application_source'] ?? null, 'result' => $data['result'], 'difficulty' => $data['difficulty'], 'total_rounds' => count($data['rounds']), 'duration_days' => $data['duration_days'] ?? null, 'overall_experience' => $data['overall_experience'], 'preparation_advice' => $data['preparation_advice'] ?? null, 'moderation_status' => 'pending']);
            foreach (array_values($data['rounds']) as $i => $round) $experience->rounds()->create(['round_number' => $i + 1, 'round_name' => $round['round_name'], 'round_type' => $round['round_type'], 'duration_minutes' => $round['duration_minutes'] ?? null, 'difficulty' => $round['difficulty'] ?? null, 'description' => $round['description'], 'topics' => $this->lines($round['topics'] ?? ''), 'questions' => $this->lines($round['questions'] ?? ''), 'candidate_experience' => $round['candidate_experience'] ?? null, 'is_elimination_round' => !empty($round['is_elimination_round'])]);
            if (collect(['base_salary', 'bonus', 'stock_value', 'total_compensation'])->contains(fn($key) => isset($data[$key]) && $data[$key] !== null)) $experience->package()->create(['company_id' => $company->id, 'role_id' => $role->id, 'location' => $data['location'], 'experience_years' => $data['experience_years'] ?? null, 'base_salary' => $data['base_salary'] ?? null, 'bonus' => $data['bonus'] ?? null, 'stock_value' => $data['stock_value'] ?? null, 'total_compensation' => $data['total_compensation'] ?? null, 'currency' => $data['currency'] ?? 'INR', 'source_type' => 'candidate_report', 'notes' => $data['salary_notes'] ?? null, 'is_verified' => false]);
        });
        return redirect()->route('company.experiences.company', $company->slug)->with('success', 'Thank you. Your experience was submitted for moderation.');
    }

    public function compare(Request $request)
    {
        $ids = collect($request->input('companies', []))->map(fn($id) => (int)$id)->filter()->unique()->take(4);
        $allCompanies = InterviewCompany::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $companies = InterviewCompany::whereIn('id', $ids)->with(['experiences' => fn($q) => $q->where('is_published', true)->with('rounds'), 'packages' => fn($q) => $q->where('is_verified', true)])->get();
        return view('company-experiences.compare', compact('allCompanies', 'companies'));
    }
    private function lines(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n|,/', $value))->map(fn($v) => trim($v))->filter()->unique()->values()->all();
    }
}
