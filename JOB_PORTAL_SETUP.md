# Job Portal Project - Setup & Implementation Guide

## Overview
This is a job aggregation portal built with Laravel that scrapes job listings from company career pages and presents them like a news feed (similar to naukri.com).

## Project Structure

### Database Schema
- **companies** - Company information and career page URLs
- **jobs** - Job listings with detailed information
- **job_categories** - Job role categories (SDE, PM, etc.)
- **technologies** - Technology stack (PHP, Laravel, Python, etc.)
- **job_category** - Pivot table linking jobs to categories
- **job_technology** - Pivot table linking jobs to technologies

### Models
- `Company` - Represents a hiring company
- `Job` - Represents a job listing
- `JobCategory` - Represents a job role type
- `Technology` - Represents a technology/skill

### API Endpoints

#### Jobs Endpoints
```
GET /api/jobs                              # Get all jobs with filters
GET /api/jobs/{id}                         # Get job details
GET /api/jobs/recent                       # Get recently posted jobs
GET /api/jobs/trending                     # Get trending jobs by views
GET /api/jobs/stats                        # Get job statistics
GET /api/jobs/company/{companyId}          # Get jobs by company
```

**Query Parameters:**
- `country` - Filter by country (India, USA, etc.)
- `location` - Filter by location
- `company_id` - Filter by company
- `job_type` - Filter by job type (Full-time, Contract, etc.)
- `experience_level` - Filter by level (Entry, Mid, Senior)
- `technology_id` - Filter by technology
- `category_id` - Filter by job category
- `q` - Search query
- `salary_min`, `salary_max` - Filter by salary range
- `sort_by` - Sort by field (posted_at, salary, views)
- `sort_order` - Sort order (asc, desc)
- `per_page` - Results per page (default 15)

#### Companies Endpoints
```
GET /api/companies                         # Get all companies
GET /api/companies/{id}                    # Get company details
GET /api/companies/top-hiring              # Get top hiring companies
GET /api/companies/country/{country}       # Get companies by country
GET /api/companies/countries               # Get all available countries
GET /api/companies/sectors                 # Get all sectors
```

#### Technologies Endpoints
```
GET /api/technologies                      # Get all technologies
GET /api/technologies/{id}                 # Get technology details with jobs
GET /api/technologies/trending             # Get trending technologies
GET /api/technologies/categories           # Get all tech categories
GET /api/technologies/category/{category}  # Get technologies by category
```

## Setup Instructions

### 1. Run Migrations
```bash
php artisan migrate
```

This creates all database tables:
- companies
- job_categories
- technologies
- jobs
- job_category (pivot)
- job_technology (pivot)

### 2. Seed Initial Data
```bash
php artisan db:seed
```

Or seed specific seeders:
```bash
php artisan db:seed --class=CompanySeeder
php artisan db:seed --class=TechnologySeeder
php artisan db:seed --class=JobCategorySeeder
```

This will populate:
- 45+ companies from various sectors
- 60+ technologies and skills
- 27 job categories (SDE, PM, Data Engineer, etc.)

### 3. Test API Endpoints
Once seeded, test the API:

```bash
# Get all jobs
curl http://localhost:8000/api/jobs

# Get jobs from India
curl "http://localhost:8000/api/jobs?country=India"

# Get trending jobs
curl http://localhost:8000/api/jobs/trending

# Get companies
curl http://localhost:8000/api/companies

# Get job statistics
curl http://localhost:8000/api/jobs/stats
```

## Web Scraping Setup

### Understanding the Scraping System

The scraping system is built with flexibility in mind:

1. **Base Scraper** (`app/Services/JobScraper.php`) - Provides common scraping functionality
2. **Company-Specific Scrapers** (`app/Services/Scrapers/`) - Override for each company
3. **Console Command** (`app/Console/Commands/ScrapeJobs.php`) - Trigger scraping manually
4. **Scheduled Jobs** - Set up in kernel.php for automatic scraping

### Implementing Company Scrapers

Each company's career page has different HTML structure. Here's how to create a scraper:

**Step 1: Create Company-Specific Scraper**

```php
<?php
namespace App\Services\Scrapers;

use App\Models\Company;
use App\Services\JobScraper;

class CompanyNameScraper extends JobScraper
{
    protected function scrapeCompanyJobs(Company $company): array
    {
        // 1. Get career page content
        $content = $this->getContent($company->careers_url);
        $crawler = $this->parseHtml($content);
        
        // 2. Parse job listings
        $jobs = [];
        $crawler->filter('.job-listing')->each(function($node) use (&$jobs) {
            $jobs[] = [
                'title' => $node->filter('.job-title')->text(),
                'description' => $node->filter('.job-desc')->text(),
                'location' => $node->filter('.location')->text(),
                'country' => 'India',
                'external_url' => $node->filter('a')->attr('href'),
                'job_type' => 'Full-time',
                'posted_at' => now(),
            ];
        });
        
        return $jobs;
    }
}
```

**Step 2: Analyze Company's HTML**

Use browser developer tools to identify:
- CSS selectors for job listings
- Job title, description, location
- Apply button URLs
- Posted date

**Step 3: Test the Scraper**

```bash
# Scrape specific company
php artisan jobs:scrape --company=1

# Where company ID 1 is Google, 2 is Microsoft, etc.
```

### Schedule Scraping

Edit `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Scrape all companies daily at midnight
    $schedule->command('jobs:scrape')->dailyAt('00:00');
    
    // Or scrape every 6 hours
    $schedule->command('jobs:scrape')->everyTwoHours();
    
    // Or scrape every hour
    $schedule->command('jobs:scrape')->hourly();
}
```

Then start the scheduler:
```bash
# For development/testing
php artisan schedule:run

# For production (add to cron)
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Frontend Implementation

### Key Features to Build

1. **Job Listing Page**
   - Display jobs in card format
   - Pagination
   - Search bar

2. **Filters Sidebar**
   - Company filter
   - Location filter
   - Technology filter
   - Job category filter
   - Experience level filter
   - Salary range filter

3. **Job Detail Page**
   - Full job description
   - Company info
   - Required technologies
   - External application link
   - Similar jobs

4. **Company Directory**
   - Company listings
   - Company details with recent jobs
   - Top hiring companies

5. **Technology Explorer**
   - Browse technologies
   - Jobs by technology
   - Trending tech stack

### Frontend Stack Recommendation
- **Vue.js 3** or **React** with TypeScript
- **Tailwind CSS** for styling
- **Axios** for API calls
- **Vue Router** or **React Router** for navigation

### Example Vue.js Component

```vue
<template>
  <div class="job-listing">
    <div class="filters">
      <input v-model="filters.q" placeholder="Search jobs..." />
      <select v-model="filters.country">
        <option value="">All Countries</option>
        <option v-for="country in countries" :value="country">{{ country }}</option>
      </select>
    </div>
    
    <div class="jobs">
      <div v-for="job in jobs" :key="job.id" class="job-card">
        <h3>{{ job.title }}</h3>
        <p class="company">{{ job.company.name }}</p>
        <p class="location">{{ job.location }} - {{ job.country }}</p>
        <div class="tags">
          <span v-for="tech in job.technologies" :key="tech.id" class="tag">{{ tech.name }}</span>
        </div>
        <a :href="job.external_url" target="_blank" class="apply-btn">Apply</a>
      </div>
    </div>
    
    <div class="pagination">
      <button v-if="currentPage > 1" @click="previousPage">Previous</button>
      <span>Page {{ currentPage }} of {{ lastPage }}</span>
      <button v-if="currentPage < lastPage" @click="nextPage">Next</button>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      jobs: [],
      filters: {
        q: '',
        country: '',
        technology_id: '',
        category_id: '',
      },
      currentPage: 1,
      lastPage: 1,
      countries: [],
    };
  },
  mounted() {
    this.fetchJobs();
    this.fetchCountries();
  },
  methods: {
    async fetchJobs() {
      const response = await axios.get('/api/jobs', {
        params: { ...this.filters, page: this.currentPage }
      });
      this.jobs = response.data.data;
      this.currentPage = response.data.pagination.current_page;
      this.lastPage = response.data.pagination.last_page;
    },
    async fetchCountries() {
      const response = await axios.get('/api/companies/countries');
      this.countries = response.data.data;
    },
    previousPage() {
      this.currentPage--;
      this.fetchJobs();
    },
    nextPage() {
      this.currentPage++;
      this.fetchJobs();
    },
  },
  watch: {
    filters: {
      handler() {
        this.currentPage = 1;
        this.fetchJobs();
      },
      deep: true,
    },
  },
};
</script>
```

## Performance Optimization

### Database Indexing
All important queries are indexed:
- `jobs.company_id`
- `jobs.country`
- `jobs.location`
- `jobs.posted_at`
- `jobs.is_active`
- Full-text search on job titles and descriptions

### Caching Strategies
```php
// Cache company list
Cache::remember('companies', 60*60, function() {
    return Company::active()->get();
});

// Cache technology list
Cache::remember('technologies', 60*60, function() {
    return Technology::get();
});
```

### Pagination
Default 15 jobs per page - adjust based on performance needs.

## Next Steps

1. **Run migrations and seed data**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

2. **Test API endpoints**
   ```bash
   php artisan serve
   curl http://localhost:8000/api/jobs
   ```

3. **Implement company-specific scrapers**
   - Create scrapers for each company in `app/Services/Scrapers/`
   - Test each scraper

4. **Build frontend**
   - Create Vue/React components
   - Implement filters and search
   - Add job detail pages

5. **Set up scheduling**
   - Configure Laravel scheduler
   - Add to cron for production
   - Monitor scraping results

6. **Deploy to production**
   - Configure web server
   - Set up database
   - Enable scheduler
   - Monitor performance

## Troubleshooting

### Migrations Failed?
```bash
# Check migrations table
php artisan migrate:status

# Rollback and retry
php artisan migrate:rollback
php artisan migrate
```

### Scraper Not Finding Jobs?
- Check company career page URL in `companies` table
- Inspect HTML structure of career page
- Update CSS selectors in scraper
- Add debug logging to identify selector issues

### API Returning Empty Results?
- Verify data was seeded: `php artisan db:seed`
- Check job records: `php artisan tinker -> Job::count()`
- Verify companies are active: `php artisan tinker -> Company::active()->count()`

## Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Symfony DomCrawler](https://symfony.com/doc/current/components/dom_crawler.html)
- [Guzzle HTTP Client](https://docs.guzzlephp.org/)
- [Laravel Eloquent ORM](https://laravel.com/docs/eloquent)

## Support

For issues or questions about implementation, refer to:
1. Check the console command output for scraping errors
2. Review Laravel logs in `storage/logs/`
3. Test API endpoints directly using curl or Postman
4. Inspect database using `php artisan tinker`
