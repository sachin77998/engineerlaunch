# Industrial India

The module lives at `/industrial-areas`. Geography, companies/facilities, departments, role taxonomy and actual openings are separate database records. City/district values come from industrial area records. The extra `industrial_job_roles` table prevents role names from being seeded as openings or departments.

## Install into an existing database

Run only the new migration, then the reference seeders and the two starter imports:

```sh
php artisan migrate --path=database/migrations/2026_09_11_000001_create_industrial_directory.php
php artisan db:seed --class=IndustrialDirectorySeeder
php artisan industrial:import-areas --dry-run
php artisan industrial:import-areas
php artisan industrial:import industrial_companies
```

`IndustrialDirectorySeeder` installs 36 state/UT records, 40 departments and a separate role catalogue. It does not create companies or live jobs. The normal DatabaseSeeder also calls it.

The starter area file is the user-supplied master: 114 distinct entries, with the repeated Jaipur entry merged. Both supplied area attachments were identical. This is neither a complete national inventory nor a verified government dataset. Location names, district assignments and overlapping clusters remain subject to source verification. The UI labels these records as unverified. The company file preserves all 38 supplied examples, inactive and unverified. Generic examples such as "Industrial Companies" must be replaced with specific entities before verification. No company-to-area occupancy claims or vacancies are inferred from these files.

## CSV imports

```sh
php artisan industrial:import states
php artisan industrial:import industrial_departments
php artisan industrial:import industrial_job_roles
php artisan industrial:import industrial_areas --path=/absolute/path/areas.csv --dry-run
php artisan industrial:import industrial_areas --path=/absolute/path/areas.csv
php artisan industrial:import industrial_companies --path=/absolute/path/facilities.csv
php artisan industrial:import industrial_jobs --path=/absolute/path/openings.csv
```

Default paths are `storage/app/industrial-data/TYPE.csv`. `industrial:import-areas --file=industrial-data/industrial_areas.csv` is a compatible shorthand; `--file` must stay inside storage/app. CSVs are streamed with quoted multiline support and optional UTF-8 BOM. Blank rows are skipped; incorrect headers, row lengths, URLs, enum values and relationships fail with a row number. Each file is one transaction. A dry run rolls it back. No files are uploaded or fetched by this command.

Required columns:

| Type | Required columns | Stable key |
| --- | --- | --- |
| states | name | slug (generated from name if omitted) |
| industrial_departments | name | slug (generated if omitted) |
| industrial_job_roles | department_slug, name, slug | slug |
| industrial_areas | state_slug, name, city or district | state_slug + slug |
| industrial_companies | state_slug, area_slug, name | area + slug |
| industrial_jobs | external_id, state_slug, area_slug, job_title | external_id |

The exact supplied aliases `state`, `area`, `company` are accepted for `state_slug`, `area_slug`, `name`. Names in reference columns are normalized to slugs. `sectors` and `skills` accept semicolon or pipe separated lists. `is_active`, `is_verified`, and `is_featured` accept 0/1 or false/true. Provided blank optional cells clear values; omitted optional columns usually preserve existing values. Job company/department/role associations are a full specification per row, so provide them on each opening update. Use an explicit stable slug when renaming an entity. Include a location or plant suffix to distinguish same-name facilities inside one area. Prefix external job IDs with the provider, e.g. `employer-42:opening-918`.

Import parents first. States, areas, companies, departments and roles must exist before references to them are imported. Company-area and role-department mismatches are rejected. An area requires city or district. URLs must use HTTP(S). Coordinates and salary/experience ranges are validated. Salary amounts are INR; salary_period is monthly by default, or annual/daily/hourly. Monthly salary filters normalize annual pay and exclude daily/hourly pay rather than assuming working days/hours.

All six tables support `source_name`, `source_url`, `last_verified_at`, `verification_status`. Use ISO dates/timestamps. Status values: `unverified`, `government_source`, `company_source`, `admin_verified`, `community_verified`. Non-unverified records require a source name, URL and verification timestamp. `is_verified=1` requires official, company or admin provenance. Community reports are labelled accordingly, not as confirmed occupancy. A company is public only when active, verified, evidence is present, and its area/state is active. Facility type is optional and never inferred from a company name: Plant, Factory, Warehouse, Depot, Distribution Centre, Office, R&D Centre, Service Centre, Industrial Unit, Logistics Hub.

Import verified data from a separate reviewed CSV, using the same stable keys. Do not rerun the unverified starter company file over curated data: it explicitly sets examples inactive/unverified. The importer validates the supplied provenance fields; it does not independently certify a URL's contents. National expansion should use reviewed exports from government industrial-park systems and state agencies, retaining each record's provenance, and separately verified company facility records.

## Browsing and intelligence

- AJAX endpoints precede slug routes: `/ajax/cities?state_id={id}`, `/ajax/areas?state_id={id}&city={city}`, `/ajax/companies/{areaId}`, and `/ajax` for combined results/options.
- Canonical area URLs: `/industrial-areas/state/{stateSlug}/{areaSlug}`. Company URLs append `/company/{companySlug}`. This supports equal area slugs across states and equal company slugs across areas.
- Short `/{areaSlug}` and `/company/{companySlug}` URLs redirect only if unambiguous; ambiguous slugs return 404 instead of choosing the wrong location.
- Cascading filters reset descendants, reject mismatched parents, encode query parameters, construct options safely, cancel stale requests, and handle failures. Search spans area/city/district/state, verified companies and actual jobs; phrases such as `CNC Operator jobs near Ludhiana` use meaningful words across those fields.
- Company departments and roles are derived from its live openings. This is not a separately maintained company organizational chart. The master catalogue remains available for broader searches when no company is selected.
- Department, experience, qualification, salary and skill filters apply to openings. Area/company cards and their counts describe the location, not the filtered job subset. Experience uses overlapping advertised ranges; "Fresher" requires a minimum of zero. Qualifications use text matching, not an inferred equivalency system.
- Live jobs are active, unexpired and attached only to visible parents. Verification is labelled separately. Roles, companies and seed data never imply openings. Opening detail pages link to the supplied external application source.
- Area/company detail pages show SQL-derived live totals by department and role, and skills counted once per opening. Nearby areas use great-circle distance within 150 km, across state borders. Without coordinates the page explicitly shows other areas in the same state with no distance claim.
- Learning, interview preparation and company-experience links connect the directory to the existing portal. The owner-only Admin Industrial Area Manager is available at `/admin/industrial-areas`, alongside CLI/CSV maintenance.

## Tests

```sh
php artisan test --filter=Industrial
```

Tests explicitly connect to isolated in-memory SQLite and apply only this module's migration. They do not refresh or truncate the application database. They cover imports/rollback/dry runs, the supplied master files, provenance, hierarchy isolation, route ordering, multiword search, detail pages, live visibility, filters, nearby distances, pagination and HTML escaping.


## Owner administration

Open `/admin/industrial-areas` using an owner/admin account, or follow **Industrial Manager** from the owner header/dashboard. The manager includes all six record types, Cities / Districts, Sources, and Verification.

- Add and edit states, areas, facilities, departments, roles and openings. Editing a name/slug preserves its record ID and references. Duplicate Add submissions are rejected; CSV imports remain repeatable upserts.
- Company/area and role/department selectors load via scoped AJAX, including inactive records for owner maintenance.
- Deactivate records using the Active checkbox. No destructive delete is necessary to remove content from public discovery. Moving a facility or role to another parent is blocked while it has referenced openings.
- Correct city/district labels for a specific state/city/district group without changing other states.
- Upload CSVs up to 10 MB. Validate-only is checked by default. Uncheck it to apply a validated file. Each upload remains an atomic transaction and reports row errors.
- Review source evidence in the Sources tab and select unverified records in Verification. Edit the record to record source name, URL, verification date and status. Verifying a company also requires its Verified checkbox; Active controls publication eligibility.
- Owner/admin authorization and CSRF protection apply to all manager writes. Authorization, create/update, verification, dry runs, invalid imports, reference integrity, malformed fields and location corrections are covered by isolated tests.

Public city/district selection now includes both city names and district names. Selecting a district includes all its areas, even when each has a separate city name.


## Sector, product, process and career taxonomy

The additive `2026_09_12_000001_create_industrial_taxonomy.php` migration preserves existing job-role IDs and openings. Apply it before running `php artisan db:seed --class=IndustrialTaxonomySeeder` (the directory seeder also calls it). The supplied sector and process masters live in `IndustrialSectorSeeder` and `IndustrialProcessSeeder`; nested product paths, process sequences and image metadata live in `IndustrialTaxonomySeeder`. Career vocabulary and spelling/equivalent-title aliases live in `IndustrialCareerDictionarySeeder`.

There are 33 sectors. Nested subsectors represent product families (Automobile / Auto Components / Transmission / Gears). Brake Disc, Brake Caliper and Brake Pads are sibling products. Ordered subsector-process associations represent production steps, rather than incorrectly treating every process as a child industry. The Metal family groups steel, forging and foundry without adding a 34th sector. Shared processes can belong to multiple subsectors.

Public filters accept `sector`, `subsector` and `process` IDs and reject combinations outside the active hierarchy. Selecting a parent subsector includes its descendants. Career dictionary entries are occupations, not vacancies; equivalent-title aliases participate in job search. Sector tiles and nested gear, axle or brake products switch the hero and gallery. The 39 original supplied JPEGs are in `public/images/industries/{gear,axle,casting,forging,steel,brake}`; additional category illustrations are registered through IndustrialVisualAssetSeeder.

Curated Manesar and Sanand associations are seeded from the source manifest. Supplied vocabulary and photographs alone are not evidence that a facility performs a process. Existing free-text industry/sector fields remain for compatibility. Structured associations use `IndustrialArea::sectorCatalog()`, and `IndustrialCompany::sectors()`, `subsectors()`, and `processes()`; these are Eloquent many-to-many relations supporting `syncWithoutDetaching([$id])`. Save a company's sector together with its relevant subsectors/processes. Career associations use `IndustrialProcess::roles()`; subsector process order is stored in the pivot `sort_order`. The existing six-type admin editor does not yet edit these new classification pivots; maintain curated mappings through a project seeder or these model relations.

Additional pivots beyond the requested table list (`industrial_subsector_processes`, `industrial_company_subsectors`, `industrial_process_job_roles`) preserve shared processes, product-level company filtering and process-to-career relationships. No duplicate industrial_job_roles table is created.


## Career intelligence and cluster enrichment

Apply all migrations, then run `php artisan db:seed --class=IndustrialEnrichmentSeeder`. This adds role profiles, sector associations, related-role matching, visual metadata, career pathways and source-backed cluster records. It never creates vacancies. Existing company records and their publication choices are preserved.

The local catalog currently contains 33 sectors, 53 subsectors, 161 processes and 212 career roles. CNC Setter searches include explicit related occupations while retaining remaining location terms and job filters. ITI Fitter backgrounds map to the supplied career pathway. Exact role titles rank above aliases and related roles.

`storage/app/industrial-data/cluster-evidence.json` records company sources, source periods, evidence notes and a checked date. Historic Bosch Chassis, Alere and Ekin evidence is retained as inactive records; it is not presented as proof of a current opening. Sanand semiconductor assembly/test facilities are kept distinct from Tata-PSMC's Dholera fab. Maxxis is mapped to the Sanand tyre sector using its company contact and factory announcement.

Assets support hero, card, thumbnail and banner roles, active filtering, alt text and source metadata. The original 39 photographs remain in the project. Six built-in ImageGen category illustrations cover textiles, electronics, pharmaceuticals, logistics, tyres and automobile assembly. See `docs/industrial-visual-prompts.md` for briefs and saved paths. Generated visuals illustrate industries, not named facilities.

The header uses three additional vertical dropdowns: About (About Us, Contact), Explore (Company Experiences, Industries, Latest in Tech), and Account (HR Login, Owner Login, Student Sign Up).
