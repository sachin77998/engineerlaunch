# Live deployment and employer coverage

## Confirmed live fault
The public `/career-explorer` endpoint still returned HTTP 500 on 1 October 2026: `naukri.career_tracks` does not exist. The public exception identifies the running Laravel root as `/home/ysi7v3oawbpa/career-portal/naukri-com`. The cPanel Git checkout is `/home/ysi7v3oawbpa/repositories/engineerlaunch`; pulling that checkout is not proof of deploying the running application.

Commit 3ce8140 makes Career Explorer tolerant of missing reference tables and runs the idempotent `portal:prepare-discovery` before the complete migration chain in `.cpanel.yml`. It preserves existing jobs and creates/seeds missing career reference tables. The live repair remains unverified until server files and database are updated.

## File Manager / phpMyAdmin recovery
Generated artifacts are in `release/job-discovery` (not tracked in Git):
- `career-explorer-hotfix.zip`: extract in the running Laravel root, preserving app/ and config/ paths. Back up the replaced service first.
- `career-explorer.sql.gz`: import into the live `naukri` database using phpMyAdmin. Creates missing tables and upserts career reference data; no user/password changes.
- `CPANEL-CAREER-REPAIR.txt`: exact paths and cron command.

For normal deployment, use Update from Remote followed by Deploy HEAD Commit and inspect Last Deployment Information. No successful live deployment is claimed by this report.

## Employer ingestion work
The requested employer inventory covers 115 employers across 15 industries. It is a source coverage checklist, not a claim of 115 working adapters or currently hiring companies.

Dedicated public feed support now includes Bebo Technologies, Infosys global digital careers, Hexaware Oracle Recruiting, classic SuccessFactors (Capgemini), and the new public SuccessFactors search widget used by HCLTech and Wipro. Bebo imported 17 concrete openings locally. Infosys India uses a separate portal and is not represented as complete by the global feed.

HCLTech/Wipro processing streams pages, saves progress, and continues in the same queue batch. Bounded generic discovery follows official career pages, respects robots rules, discovers supported linked ATS boards, and reads JobPosting data. Incomplete discovery cannot retire previously stored vacancies. Unsupported or blocked sources remain explicit failures requiring review.

`/admin/job-sources` lists the requested employers, published opening counts, provider, last complete sync and latest batch result. Only the configured owner can queue a refresh. Workers run every minute; the database reservation timeout exceeds the ingestion worker timeout. A cPanel scheduler must actually be configured for this to operate live.

The first HTTP audit found 88 responding pages, 16 HTTP 403 responses, six outdated links and five connection failures. The outdated links were reviewed against official employer pages and corrected. An HTTP 200 alone does not prove job ingestion. Detailed initial audit: `release/job-discovery/requested-source-http-audit.csv`.

The job URL migration expands `jobs.external_url` to TEXT because genuine encoded application URLs can exceed 255 characters. This may rebuild a large MySQL table; allow deployment time and check its result.

## Remaining verification
- Apply the live files/schema repair and confirm Career Explorer and job filters work on cPanel.
- Confirm the cPanel cron runs and that queued employer imports finish.
- Review every blocked/unsupported employer source and add adapters where needed; no promise of openings is made for employers without published vacancies.
- Verify full live coverage, salary/location completeness and original application links. Previously implemented SQL lessons, visitor analytics and authentication changes still require production acceptance checks.
