# Industrial module requirement audit

This checklist covers the original hierarchy/database message, both identical area CSV attachments, the facility/controller attachment, the route/AJAX message and the final search/intelligence/import attachment. It distinguishes application implementation from verification of real-world data.

| Requirement | Delivered implementation |
| --- | --- |
| 1. Industrial India module and header entry | `/industrial-areas`; Temporary header label **Industries** before Latest, per the latest instruction; responsive search and state-grouped featured hubs. |
| 2-7. Persistent hierarchy and fields | States, areas, companies/facilities, departments and openings, with city/district fields, parent keys, indexes, coordinates, sectors, salaries, skills, deadlines and active flags. |
| 8. Models and relationships | All five requested models plus IndustrialJobRole, with relationships, JSON/date/boolean/integer casts, and consistent live/visible scopes. |
| 9-10. Departments and job roles | 40 departments and 64 roles including the supplied CNC/VMC, die, maintenance, foundry, logistics, office and facility roles. Roles are separate from departments and real openings. |
| 11-13. Importable masters and starter coverage | CSV master files under storage/app/industrial-data; 36 states/UTs; 114 distinct supplied starter areas. Duplicate Jaipur sectors merged. No claim of complete national coverage. |
| 14. Company examples | All 38 supplied examples retained inactive/unverified, including generic placeholders requiring replacement. |
| 15. Facility types | All ten requested facility types; no automatic warehouse/depot-to-plant classification. |
| 16-19. Public controller, routes and AJAX | Public index and area/company/opening details; state/city/district/area/company/department/role filters; city, area and company AJAX routes precede dynamic slugs. Parent-scoped canonical URLs prevent collisions. |
| 20. Search through companies | Company names resolve their visible areas. Search also spans location, actual job titles, qualifications and skills, including the example multiword queries. |
| 21. Area detail pages | Location, sectors, verified occupants, real opening totals, hiring breakdowns and popular skills. |
| 22. Job filters | Department, role, experience, qualification, salary and skill filters with pagination and AJAX. Dropdown controls provide the illustrated filtering dimensions. Salary filters normalize annual pay into monthly INR. |
| 23. Area job intelligence | SQL-derived live counts by department and role; skills counted from real live opening records; no fabricated counts. |
| 24. Nearby areas | Coordinates support cross-state nearest areas within 150 km. Without coordinates, the page labels suggestions as other areas in the same state and makes no distance claim. |
| 25. Source tracking | All six record types support source name/URL, verification status and last-verified timestamp; public labels and admin review screens. |
| 26. Government-data expansion | Validated CSV import pathway accepts future reviewed government/state exports after mapping to the documented columns. No giant hardcoded PHP inventory and no unsupported automatic source verification. |
| 27. Import command | `industrial:import-areas --file=...`, general `industrial:import TYPE --path=...`, dry runs, transactions, row diagnostics, BOM/multiline CSV support, safe URL validation and hierarchy validation. |
| 28. Connections to the portal | Public navigation and links to existing learning, interview preparation and company-experience pages. |
| Final attachment: Admin Industrial Area Manager | Owner-only `/admin/industrial-areas`; add/edit/deactivate records, states, cities/districts, areas, facilities, departments, roles, openings, source review, verification queue, and CSV upload/dry run/import. |

## Corrections completed during the follow-up audit

The initial delivery deferred the admin manager. That omission is now closed. The audit also added district-level browsing alongside cities, explicit foreign-key casts for driver consistency, safeguards against moving referenced facilities/roles into the wrong parents, duplicate-admin-create rejection, and owner workflow/authorization tests.

## Intentional schema and routing corrections

The supplied area migration was duplicated; it is created once. Role names are stored in a separate sixth table rather than inserting them into the department table or fabricating opening records. City/district labels remain area attributes, matching the proposed schema. Departments available for a company are derived from its real live openings; the module does not fabricate a company organizational chart.

Area slugs are unique within a state and company slugs within an area. Therefore canonical URLs include those parent paths. The supplied short area/company URLs remain supported only when unambiguous.

## Data readiness is separate from code completion

The supplied starter master has not been independently government-verified. The 38 company-area examples have not been independently verified and remain hidden as public occupants. No live opening data was supplied, so the local industrial opening table remains empty until genuine openings are added/imported. Starter coordinates were not supplied; distance-based nearby suggestions become available as coordinates are maintained.

The implementation provides the manager, verification workflow and imports to maintain these records. It does not claim that all national parks, facility occupancies or current jobs have already been collected or verified.

## Verification

Run `php artisan test --filter=Industrial` for the public directory and owner manager integration suites. These use in-memory SQLite without refreshing or truncating the application database. Browser checks cover the rendered responsive pages and cascading selections; local MySQL uses the already-applied industrial migration and starter imports.

Final audit verification: **20 integration tests passed** (12 public-directory tests and 8 owner-manager tests). The isolated browser workflow passed facility creation, scoped parent lookups, evidence-backed verification, public visibility, opening creation/search, deactivation with dependent-opening hiding, and source-review rendering. Desktop/mobile screenshots were inspected, with no page-width overflow and no JavaScript exceptions. The temporary public header label is **Industries**.
