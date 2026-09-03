<?php

namespace App\Models;

use App\Services\JobClassificationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Job extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'employer_id',
        'recruiter_company_id',
        'title',
        'slug',
        'description',
        'location',
        'country',
        'salary_min',
        'salary_max',
        'salary_currency',
        'job_type',
        'posting_source',
        'source',
        'external_job_id',
        'deduplication_key',
        'source_payload',
        'work_mode',
        'requirements',
        'responsibilities',
        'experience_level',
        'experience_min',
        'experience_max',
        'role_family',
        'external_url',
        'posted_at',
        'expires_at',
        'scraped_at',
        'views',
        'is_active',
        'status',
        'is_confidential',
        'vacancies',
        'education',
        'published_at',
        'category','department','engineering_discipline','classification_version','classified_at',
        'role','job_level','state','relocation_allowed','hiring_urgency',
        'specialization','primary_technology','salary_type','salary_period',
        'additional_compensation','application_method','application_email',
        'application_deadline','job_visibility','resume_required','cover_letter_required',
        'portfolio_required','github_required','linkedin_required',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
        'expires_at' => 'datetime',
        'scraped_at' => 'datetime',
        'is_active' => 'boolean',
        'requirements' => 'array',
        'responsibilities' => 'array',
        'source_payload' => 'array',
        'is_confidential' => 'boolean',
        'published_at' => 'datetime',
        'application_deadline' => 'date',
        'additional_compensation' => 'array',
        'classified_at' => 'datetime',
        'relocation_allowed' => 'boolean',
        'resume_required' => 'boolean',
        'cover_letter_required' => 'boolean',
        'portfolio_required' => 'boolean',
        'github_required' => 'boolean',
        'linkedin_required' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Job $job): void {
            $classificationInputs = ['title', 'role', 'category', 'description', 'requirements', 'responsibilities', 'company_id'];
            if ($job->classified_at && ! $job->isDirty($classificationInputs)) return;

            $company = $job->relationLoaded('company') ? $job->company : Company::find($job->company_id);
            $job->forceFill(app(JobClassificationService::class)->classify($job->getAttributes(), $company));
        });
    }

    /**
     * Get the company that posted this job
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all categories for this job
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(JobCategory::class, 'job_category', 'job_id', 'job_categories_id');
    }

    /**
     * Get all technologies required for this job
     */
    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class, 'job_technology', 'job_id', 'technology_id');
    }

    public function applications(): HasMany { return $this->hasMany(JobApplication::class); }
    public function locations(): HasMany { return $this->hasMany(JobLocation::class); }
    public function screeningQuestions(): HasMany { return $this->hasMany(JobScreeningQuestion::class)->orderBy('sort_order'); }
    public function skills(): BelongsToMany { return $this->belongsToMany(Skill::class, 'job_skills')->withPivot('importance'); }

    /**
     * Scope to only active jobs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'published')->where('job_visibility', 'public')->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope to filter by country
     */
    public function scopeCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope to filter by location
     */
    public function scopeLocation($query, $location)
    {
        return $query->where('location', 'LIKE', "%{$location}%");
    }

    /**
     * Scope to filter by company
     */
    public function scopeCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to filter by job type
     */
    public function scopeJobType($query, $type)
    {
        return $query->where('job_type', $type);
    }

    /**
     * Scope to filter by experience level
     */
    public function scopeExperienceLevel($query, $level)
    {
        return $query->where('experience_level', $level);
    }

    /**
     * Scope to filter by technology
     */
    public function scopeWithTechnology($query, $technologyId)
    {
        return $query->whereHas('technologies', function ($q) use ($technologyId) {
            $q->where('technology_id', $technologyId);
        });
    }

    /**
     * Scope to filter by category
     */
    public function scopeWithCategory($query, $categoryId)
    {
        return $query->whereHas('categories', function ($q) use ($categoryId) {
            $q->where('job_categories_id', $categoryId);
        });
    }

    /**
     * Scope to search by title and description
     */
    public function scopeSearch($query, $term)
    {
        $tokens = collect(preg_split('/\s+/', trim((string) $term)))
            ->filter(fn ($token) => mb_strlen($token) >= 2)
            ->unique()
            ->take(8);

        foreach ($tokens as $token) {
            $query->where(function ($searchQuery) use ($token) {
                $like = "%{$token}%";
                $searchQuery->where('title', 'LIKE', $like)
                    ->orWhere('description', 'LIKE', $like)
                    ->orWhere('requirements', 'LIKE', $like)
                    ->orWhere('location', 'LIKE', $like)
                    ->orWhereHas('company', fn ($companyQuery) => $companyQuery->where('name', 'LIKE', $like))
                    ->orWhereHas('technologies', fn ($technologyQuery) => $technologyQuery->where('name', 'LIKE', $like));
            });
        }

        return $query;
    }

    /**
     * Scope to filter by salary range
     */
    public function scopeSalaryRange($query, $min, $max)
    {
        return $query->where('salary_currency', 'INR')
            ->where('salary_max', '>=', $min)
            ->where('salary_min', '<=', $max);
    }

    /**
     * Scope to order by newest first
     */
    public function scopeNewest($query)
    {
        return $query->orderBy('posted_at', 'desc');
    }

    /**
     * Increment view count
     */
    public function incrementViews(): void
    {
        $this->increment('views');
    }
}
