# Industrial recruitment coverage

The industrial directory records plants separately from vacancies. A government estate directory establishes an address; it does not establish that an employer is hiring.

## Data path

Official estate / manufacturer evidence -> verified company and location -> official careers source -> fetched vacancy -> company + city match -> department / trade role -> opening.

## Operations

- `php artisan db:seed --class=IndustrialRecruitmentSeeder --force` registers sources, role vocabulary and verified facilities idempotently.
- `php artisan industrial:sources` shows active database counts and last successful sync.
- `php artisan industrial:sources --sync` fetches all configured industrial employers.
- Existing `jobs:scrape` also includes these enabled sources and now maps imported vacancies to the industrial directory.
- `php artisan industrial:discover-careers --limit=50 --after=0` researches career links on verified company websites. It records links as evidence, never as jobs. Resume using the printed company ID.
- Existing industrial CSV import supports bulk company onboarding with provenance. Directory records without verified location evidence must remain unverified.

## Implemented sources

| Employer | Import method |
|---|---|
| Happy Forgings | Official vacancy list and detail pages; unspecified job locations stay unassigned |
| Sonalika | Structured public career-page data, including location and job ID |
| Tata Motors | SuccessFactors, all result pages |
| Hero MotoCorp | SuccessFactors, all result pages |
| Mahindra | SuccessFactors, all result pages |
| Additional small manufacturers | JobPosting JSON-LD adapter available; verify and configure each official career URL |

The registry also records SIIDCUL, PSIEC, Happy Forgings, Euro Cast & Forge, GNA Axles, Vardhman, Hawkins, Nestle, Britannia and Ashok Leyland research channels. These are not all automated feeds. Happy Forgings is now imported through PHP after an initial download failure; its vacancy pages do not establish a specific work location. Historical advertisements are not imported as current openings.

## Matching and limitations

- Exact normalized company identity or official website domain is required.
- City evidence is required; a state or brand name alone cannot assign a plant.
- Multiple same-company plants in one city remain unmatched until more precise evidence is available.
- Inactive, private, draft and expired generic records cannot create active industrial copies.
- Empty or failed fetches preserve existing listings; successful complete scrapes retire missing records and their industrial copies.
- No 100,000-company coverage is claimed. Missing websites, protected portals, vacancies advertised only offline and unsupported career formats still require further research/adapters.
- Windows TLS uses an optional locally exported trust bundle; `JOB_SCRAPER_CA_BUNDLE` can point to a maintained CA bundle. Certificate validation stays enabled. The local bundle is not committed.
- Typical qualifications and factory process diagrams are learning guidance, not employer-specific eligibility requirements.

## Verification

Isolated SQLite tests cover company/plant mismatches, ambiguous plants, expired jobs, source identity, structured vacancy parsing and variable SuccessFactors pagination. Browser checks cover the factory learning table at desktop and mobile widths.
