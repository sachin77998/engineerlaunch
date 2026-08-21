# Job Portal - Commands Quick Reference

## Database Setup

```bash
# Run all migrations
php artisan migrate

# Rollback last batch of migrations
php artisan migrate:rollback

# Rollback everything and migrate fresh (⚠️ deletes all data)
php artisan migrate:fresh

# Seed initial data
php artisan db:seed

# Seed specific seeder
php artisan db:seed --class=CompanySeeder
php artisan db:seed --class=TechnologySeeder
php artisan db:seed --class=JobCategorySeeder

# Refresh database and seed (⚠️ fresh + seed)
php artisan migrate:fresh --seed

# Check migration status
php artisan migrate:status
```

## Running the Application

```bash
# Start development server (default: http://127.0.0.1:8000)
php artisan serve

# Start on specific port
php artisan serve --port=8001

# Start with public URL
php artisan serve --host=0.0.0.0

# Generate new APP_KEY (if missing from .env)
php artisan key:generate
```

## Web Scraping

```bash
# Scrape all companies
php artisan jobs:scrape

# Scrape specific company by ID
php artisan jobs:scrape --company=1

# List company IDs
php artisan tinker
> App\Models\Company::active()->pluck('id', 'name')
```

## Database Interaction

```bash
# Open Laravel Tinker (interactive shell)
php artisan tinker

# Inside tinker:
# Get all jobs
Job::count()

# Get all companies
Company::active()->count()

# Get jobs from specific company
Job::where('company_id', 1)->get()

# Search jobs
Job::search('developer')->get()

# Create sample job
$company = Company::find(1);
$job = Job::create([
    'company_id' => $company->id,
    'title' => 'Senior Developer',
    'description' => 'Looking for...',
    'location' => 'Bangalore',
    'country' => 'India',
    'external_url' => 'https://...',
    'posted_at' => now(),
]);

# Add technologies to job
$java = Technology::where('name', 'Java')->first();
$spring = Technology::where('name', 'Spring Boot')->first();
$job->technologies()->attach([$java->id, $spring->id]);

# Exit tinker
exit
```

## API Testing

```bash
# Using curl

# Get all jobs
curl http://localhost:8000/api/jobs

# Get jobs by country
curl "http://localhost:8000/api/jobs?country=India"

# Search jobs
curl "http://localhost:8000/api/jobs?q=developer"

# Get trending jobs
curl http://localhost:8000/api/jobs/trending

# Get job statistics
curl http://localhost:8000/api/jobs/stats

# Get all companies
curl http://localhost:8000/api/companies

# Get top hiring companies
curl http://localhost:8000/api/companies/top-hiring

# Get technologies
curl http://localhost:8000/api/technologies

# Get trending technologies
curl http://localhost:8000/api/technologies/trending
```

## Code Generation

```bash
# Generate new migration
php artisan make:migration create_users_table

# Generate new model with migration
php artisan make:model Job -m

# Generate controller
php artisan make:controller Api/JobController

# Generate command
php artisan make:command ScrapeJobs

# Generate seeder
php artisan make:seeder CompanySeeder

# Generate service class (no built-in, create manually)
# mkdir app/Services
# Create file: app/Services/JobScraper.php
```

## Cache & Config

```bash
# Clear all caches
php artisan cache:clear

# Clear configuration cache
php artisan config:clear

# Clear route cache
php artisan route:cache

# Clear view cache
php artisan view:clear

# Optimize framework
php artisan optimize

# View current configuration
php artisan config:show
```

## Testing

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter=JobTest

# Run tests with coverage
php artisan test --coverage

# Run tests in parallel
php artisan test --parallel
```

## Debugging

```bash
# Enable debug mode (in .env)
APP_DEBUG=true

# View logs
tail -f storage/logs/laravel.log

# Check environment
php artisan env

# List all routes
php artisan route:list

# List all routes matching pattern
php artisan route:list --name=jobs

# Check class dependencies
php artisan tinker
> App\Models\Job::with('company', 'technologies')->first()
```

## Development Helpers

```bash
# Check Laravel version
php artisan --version

# Display help
php artisan help

# List all available commands
php artisan list

# Composer install/update
composer install
composer update

# Install package
composer require guzzlehttp/guzzle

# Check PHP version
php --version

# Check PHP extensions
php -m | grep mysql
php -m | grep curl
```

## Database Utilities

```bash
# Create database backup
mysqldump -u root naukri-com > backup.sql

# Restore database
mysql -u root naukri-com < backup.sql

# Access MySQL
mysql -u root

# Inside MySQL:
USE naukri-com;
SHOW TABLES;
SELECT COUNT(*) FROM jobs;
DESC jobs;
```

## Production Deployment

```bash
# Clear everything and optimize
php artisan cache:clear
php artisan config:clear
php artisan route:cache
php artisan view:clear
php artisan optimize

# Set production environment
APP_ENV=production
APP_DEBUG=false

# Generate key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Set up scheduler (add to crontab)
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1

# Monitor logs
tail -f storage/logs/laravel.log
```

## Common Issues & Solutions

```bash
# "Class not found" error
composer dump-autoload

# Permission denied on storage
chmod -R 775 storage bootstrap/cache

# Database connection error
# Check .env file:
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=naukri-com
DB_USERNAME=root
DB_PASSWORD=

# Clear config after changing .env
php artisan config:clear

# Memory limit exceeded
# In php.ini: memory_limit=256M
# Or in artisan: php -d memory_limit=256M artisan ...
```

## File Locations Reference

```
c:\xampp\htdocs\NaukriCOMPROJECT\naukri-com\
├── app/
│   ├── Models/              # Database models
│   ├── Http/Controllers/Api/ # API controllers
│   ├── Services/            # Business logic
│   └── Console/Commands/    # CLI commands
├── database/
│   ├── migrations/          # Database migrations
│   └── seeders/             # Database seeders
├── routes/
│   └── api.php              # API routes
├── storage/
│   └── logs/                # Application logs
├── .env                     # Environment configuration
├── config/                  # Configuration files
└── artisan                  # CLI entry point
```

## Laravel Artisan Cheat Sheet

```
serve              Start development server
migrate            Run database migrations
seed               Seed database with data
tinker             Open interactive shell
route:list         List all routes
cache:clear        Clear application cache
config:clear       Clear config cache
key:generate       Generate APP_KEY
make:*             Generate new files (model, migration, etc.)
queue:work         Start queue worker
schedule:run       Run scheduled jobs
down               Put application in maintenance mode
up                 Bring application out of maintenance
```

---

**💡 Tip:** Most of these commands have help text available:
```bash
php artisan help [command-name]
# Example:
php artisan help migrate
```
