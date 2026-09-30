# Job coverage requirements audit - 30 September 2026

Result: NOT COMPLETE. This is an audit of the two supplied company/job-feed
attachments against current code, local database and public production APIs.
A directory listing, career URL or parser class alone is not completed ingestion.

## Live evidence
- /api/jobs/stats: 30,169 active jobs, 34 hiring companies, 658,794 directory
  companies, zero technologies. Directory size does not establish hiring coverage.
- /api/jobs?per_page=1: HTTP 200, 30,169 matches.
- /api/jobs/locations: HTTP 200, 250 countries and seven regions.
- /api/jobs/locations?country=IN&state=Punjab: HTTP 200, 36 Indian states,
  100 city options (the configured limit).
- /api/jobs?country=IN&per_page=1, FR and DE: HTTP 500.
  Confirmed error: 134,217,728-byte PHP memory limit exhausted in GeographyCatalog.
- /api/jobs?q=Java&per_page=1: HTTP 200, 1,667 matches.
- /api/jobs?q=Python&per_page=1: HTTP 200, 2,670 matches.
- /api/technologies?per_page=1: total zero.
- /api/companies?q=HCL and q=Infosys: no companies with active jobs returned.
- /api/companies?include_empty=1&q=Capgemini: Capgemini has zero active jobs
  and sync_enabled=false.
- Live stats: 29,277 jobs have no experience_level. Job-type labels use multiple
  spellings (Full Time, FULL_TIME, Full-time, etc.). Filtering completeness is
  not established merely by having UI controls.
These are point-in-time application results, not independently verified vacancy
counts on every employer website.

## Requirement matrix

| Requirement | Status | Evidence / gap |
|---|---|---|
| One daily batch for current openings | Partial | jobs:dispatch-daily, per-company queue jobs, retries and audit records exist. It selects only enabled company sources. |
| Every named employer covered | Not complete | Source coverage is a small subset; see company checks below. |
| Latest vacancies refreshed automatically in cPanel | Unverified | Laravel schedule exists; no authenticated access to inspect cPanel cron, queue execution or live batch logs. |
| Search jobs by programming skills | Partial | Java/Python work live; technology catalog is empty. All requested skills have not been verified end-to-end. |
| Country/state/city filtering | Broken on live at audit | Options load, but country filtering exhausts PHP memory. A streaming fix is being validated separately. |
| Europe, France and Germany coverage | Partial | Some jobs exist, but named employer coverage is incomplete and country filter failed. |
| Banks in Switzerland/Scotland/US and insurance | Not established | No proof of working ingestion for every named bank/insurer. Directory records are insufficient. |
| Bearings/forging/steel/electrical/automobile | Partial | Five industrial feeds configured; this does not cover the full requested manufacturer list. |
| Dairy/FMCG/soap/textile/petroleum/gas | Not complete | No complete verified source-to-opening coverage demonstrated for requested companies. |
| Semiconductors, batteries, solar and renewables | Partial | Some semiconductor employers are enabled; the full requested list is not covered. |
| All code for one batch | Framework exists | Existing command/job/service are source-controlled, but missing company adapters/configuration remain. |

## Exact company checks in local database

All of these exact company records have ZERO active jobs, synchronization disabled
and no last successful synchronization timestamp:
- Infosys: no adapter configured.
- TCS: no adapter configured.
- Hexaware Technologies: no adapter configured.
- Tech Mahindra: no adapter configured.
- Atos: no adapter configured.
- Wipro: SuccessFactors provider recorded, synchronization disabled.
- HCLTech: SuccessFactors provider recorded, synchronization disabled.
- Capgemini: SuccessFactors provider recorded, synchronization disabled.
- Amdocs: SuccessFactors provider recorded, synchronization disabled.

OfficialCareerSourceSeeder explicitly disables HCLTech, Capgemini and Amdocs;
Infosys/TCS/Hexaware have career URLs without feed adapters. Re-running that seeder
does not implement their missing importers.

Exact-name checks did not return curated company records for several other
requested names, including Bebo Technologies, Tata Technologies, Sopra Steria,
Reply, GFT Technologies, Tietoevry, Alten, Inetum, Devoteam, Neurones, Wavestone,
Bechtle, Cancom, Amul, Verka, Havells, JSW Steel and Hindustan Unilever.
Legal entities/aliases may exist in the large registry; exact-name absence does
not prove absence under all aliases, and registry presence does not prove a feed.

## Remaining attachment coverage checklist

Every name below needs a verified career source, working import/refresh or an
explicit documented source limitation; current vacancies must not be fabricated.

- European IT: Sopra Steria, Atos, Reply, GFT, Tietoevry, Alten, CGI, Inetum,
  Devoteam, Neurones, Wavestone, IBM Consulting, msg group, adesso, Ferchau,
  Bechtle, Cancom; validate Accenture's region-specific openings.
- US IT: Cognizant, DXC, EPAM, Peraton, Leidos.
- Banks: UBS, Raiffeisen Switzerland, ZKB, BCV, Pictet, Julius Baer, NatWest/RBS,
  Lloyds/Bank of Scotland, TSB, Virgin Money, BNP Paribas. The pasted US Big Four
  section does not enumerate bank names; do not treat that as verified coverage.
- Bearings/industrial/electrical: NBC/NEI, SKF, Timken, Schaeffler, NRB, Ramkrishna
  Forgings, Electrosteel, Tata tubes/steel businesses, Havells, Crompton,
  Schneider Electric.
- Dairy/FMCG/oil/gas/textile: Amul, Verka, Kwality Wall's, Reliance/refineries,
  Hindustan Petroleum, Bharatgas, Gujarat Gas, Fortune; logistics, ghee, edible
  oil, cotton/apparel and textile employers need explicit source inventories.
- Semiconductors: Texas Instruments, Analog Devices, NXP, Infineon, Microchip,
  onsemi.
- Insurance: Star Health, HDFC (resolve exact insurance entity), Oriental, PNB
  (resolve bank versus insurer), LIC and other explicitly identified insurers.
- Metals: JSW Steel, Tata Steel, SAIL, Jindal Steel, AM/NS India, Jindal Stainless.
- Renewables: Adani Green, Tata Power, NTPC Green, ReNew.
- Batteries: CATL, BYD, LG Energy Solution, Panasonic, Samsung SDI, SK On, Exide,
  Amara Raja, Eastman Auto & Power, Eveready.
- Solar: Waaree, Tata Power Solar, Adani Solar, Vikram Solar, Loom Solar, Luminous.
- Consumer/soap: Hindustan Unilever, Godrej Consumer Products, Wipro Consumer Care,
  Himalaya, Anuspa Heritage, HCP Wellness, Charkha/Duke Soaps.

Brand/subsidiary ambiguity must be resolved against official careers sites rather
than creating duplicate employers with guessed openings.

## Definition of done
For each employer: canonical identity, official source URL, compatible adapter,
successful timestamped batch, actual imported job count, searchable title/skills/
location, working original apply URL, duplicate handling and expiry handling.
A source failure or no current vacancies must be reported distinctly.
For production: passing filtered API requests, populated skills, and recorded
successful scheduled runs after deployment. None of these can be inferred from
a large company-directory count.

## Current correction
Country matching is changed to stream jobs in chunks, retain bounded location
lookups and cache matching IDs instead of materializing/caching the full job
geography index. This fixes the observed memory design issue; production
verification still requires deployment.

Validation of the correction: country/state/city regression tests passed.
A full local catalogue run under memory_limit=128M returned India matches with
48 MB peak memory and 22.81 seconds cold execution on this development machine.
Runtime and counts may differ in production; this is not live verification.
