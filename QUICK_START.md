# Job Portal - Quick Start Guide

## 5-Minute Setup

### Step 1: Run Migrations
```bash
cd c:\xampp\htdocs\NaukriCOMPROJECT\naukri-com
php artisan migrate
```

**Expected output:** You should see messages about creating tables for companies, jobs, technologies, etc.

### Step 2: Seed Initial Data
```bash
php artisan db:seed
```

**Expected output:** 
- Companies seeded successfully!
- Technologies seeded successfully!
- Job categories seeded successfully!

### Step 3: Start Laravel Server
```bash
php artisan serve
```

Server will run at: `http://127.0.0.1:8000`

### Step 4: Test API Endpoints

Open browser or use curl:

**Get all jobs:**
```
http://localhost:8000/api/jobs
```

**Get recently posted jobs:**
```
http://localhost:8000/api/jobs/recent
```

**Get jobs statistics:**
```
http://localhost:8000/api/jobs/stats
```

**Get all companies:**
```
http://localhost:8000/api/companies
```

**Get top hiring companies:**
```
http://localhost:8000/api/companies/top-hiring
```

**Get all technologies:**
```
http://localhost:8000/api/technologies
```

## Database Setup

Your database is configured in `.env`:
```
DB_DATABASE=naukri-com
DB_USERNAME=root
DB_PASSWORD=
```

Make sure MySQL is running (XAMPP).

## File Structure

Created files:
```
├── app/
│   ├── Models/
│   │   ├── Company.php          ✓ Created
│   │   ├── Job.php              ✓ Created
│   │   ├── JobCategory.php      ✓ Created
│   │   └── Technology.php       ✓ Created
│   ├── Http/Controllers/Api/
│   │   ├── JobController.php    ✓ Created
│   │   ├── CompanyController.php ✓ Created
│   │   └── TechnologyController.php ✓ Created
│   ├── Services/
│   │   ├── JobScraper.php       ✓ Created
│   │   └── Scrapers/
│   │       └── GoogleScraper.php ✓ Created
│   └── Console/Commands/
│       └── ScrapeJobs.php       ✓ Created
├── database/migrations/
│   ├── 2024_08_20_000001_create_companies_table.php
│   ├── 2024_08_20_000002_create_job_categories_table.php
│   ├── 2024_08_20_000003_create_technologies_table.php
│   ├── 2024_08_20_000004_create_jobs_table.php
│   ├── 2024_08_20_000005_create_job_category_table.php
│   └── 2024_08_20_000006_create_job_technology_table.php
├── database/seeders/
│   ├── CompanySeeder.php        ✓ Created (45+ companies)
│   ├── TechnologySeeder.php     ✓ Created (60+ technologies)
│   ├── JobCategorySeeder.php    ✓ Created (27 categories)
│   └── DatabaseSeeder.php       ✓ Updated
├── routes/
│   └── api.php                  ✓ Updated (all API routes)
└── JOB_PORTAL_SETUP.md          ✓ Created (detailed guide)
```

## Included Companies (45+)

**Big Tech:** Microsoft, Google, Amazon, Meta, Apple

**Enterprise Software:** Oracle, Salesforce, SAP, Adobe, ServiceNow, Workday, VMware, Snowflake, MongoDB, Datadog

**IT Services:** TCS, Infosys, Wipro, Cognizant, Accenture, IBM, Capgemini, HCLTech, Tech Mahindra, Amdocs

**Cybersecurity:** Palo Alto Networks, CrowdStrike, Fortinet, Zscaler, Cloudflare

**E-commerce (India):** Flipkart, Myntra, Zomato, Blinkit, Swiggy

**Hardware:** Cisco, Dell, HP

**Semiconductors:** NVIDIA, Intel, AMD, Qualcomm

**Banking:** Bank of America, Deutsche Bank, HSBC, Citi, Standard Chartered, SoftBank

## Included Technologies (60+)

**Backend:** PHP, Python, Java, C++, C#, C, Go, Rust, Ruby, TypeScript, Node.js

**Frontend:** React, Vue.js, Angular, HTML5, CSS3, JavaScript, Svelte, Next.js

**Frameworks:** Laravel, Spring Boot, Django, .NET, Express.js, Rails, Flask

**Databases:** MySQL, PostgreSQL, MongoDB, Redis, Elasticsearch, Cassandra, Oracle, SQL Server

**Cloud/DevOps:** AWS, Azure, Google Cloud, Docker, Kubernetes, CI/CD, Jenkins, Terraform, Ansible

**ML/AI:** Machine Learning, Deep Learning, AI Engineer, Data Science, TensorFlow, PyTorch, Scikit-learn, NLP, Computer Vision

**Mobile:** Android, iOS, React Native, Flutter, Swift, Kotlin

**Other:** GraphQL, REST API, Microservices, System Design, Git, Linux, Agile/Scrum, JIRA

## Included Job Categories (27)

Software Developer, SDE 2, SDE 3, Senior Software Engineer, Staff Engineer, Principal Engineer, Product Manager, Senior PM, Scrum Master, Technical Lead, Engineering Manager, Data Engineer, Data Scientist, ML Engineer, AI Engineer, DevOps Engineer, Cloud Architect, Security Engineer, Frontend Developer, Backend Developer, Full Stack Developer, Mobile Developer, QA Engineer, Solutions Architect, Systems Architect, Internship, Graduate Program

## Next: Manual Job Entry

No real jobs are scraped yet. You can manually add sample jobs:

```bash
php artisan tinker
```

```php
// Add a sample job
$company = App\Models\Company::where('name', 'Google')->first();
$job = App\Models\Job::create([
    'company_id' => $company->id,
    'title' => 'Senior Software Engineer - Backend',
    'description' => 'We are looking for a Senior Software Engineer...',
    'location' => 'Bangalore, India',
    'country' => 'India',
    'salary_min' => 1200000,
    'salary_max' => 1800000,
    'job_type' => 'Full-time',
    'experience_level' => 'Senior',
    'external_url' => 'https://careers.google.com/jobs/...',
    'posted_at' => now(),
]);

// Attach technologies
$job->technologies()->attach([
    App\Models\Technology::where('name', 'Java')->first()->id,
    App\Models\Technology::where('name', 'Spring Boot')->first()->id,
    App\Models\Technology::where('name', 'Kubernetes')->first()->id,
]);

// Attach categories
$job->categories()->attach([
    App\Models\JobCategory::where('name', 'Senior Software Engineer')->first()->id,
]);

$job
```

Then visit: `http://localhost:8000/api/jobs`

## Troubleshooting

### Error: SQLSTATE[HY000]: General error
```
php artisan cache:clear
php artisan config:clear
php artisan migrate
```

### Error: Column 'name' doesn't exist
Make sure you ran migrations first:
```
php artisan migrate --fresh  # This wipes and recreates all tables
```

### Port 8000 already in use?
```bash
php artisan serve --port=8001
```

## What's Next?

1. ✅ Database setup complete
2. ✅ API endpoints ready
3. ✅ Seeders with 45+ companies
4. 🔄 Build web scrapers for each company (see JOB_PORTAL_SETUP.md)
5. 🔄 Create frontend UI (Vue/React)
6. 🔄 Set up scheduled scraping

## API Response Example

```json
GET /api/jobs

{
  "success": true,
  "data": [
    {
      "id": 1,
      "company_id": 1,
      "title": "Senior Software Engineer",
      "description": "Job description...",
      "location": "Bangalore, India",
      "country": "India",
      "salary_min": "1200000.00",
      "salary_max": "1800000.00",
      "job_type": "Full-time",
      "experience_level": "Senior",
      "external_url": "https://careers.google.com/...",
      "posted_at": "2024-08-20T12:00:00Z",
      "views": 0,
      "is_active": true,
      "company": {
        "id": 1,
        "name": "Google",
        "country": "USA"
      },
      "technologies": [
        {"id": 1, "name": "Java", "category": "Backend"},
        {"id": 2, "name": "Spring Boot", "category": "Backend"}
      ],
      "categories": [
        {"id": 3, "name": "Senior Software Engineer"}
      ]
    }
  ],
  "pagination": {
    "total": 100,
    "per_page": 15,
    "current_page": 1,
    "last_page": 7
  }
}
```

## Questions?

Refer to:
- `JOB_PORTAL_SETUP.md` - Comprehensive setup guide
- `routes/api.php` - All API routes
- `app/Http/Controllers/Api/` - API controllers with detailed comments
- `database/seeders/` - Seeder code with sample data
