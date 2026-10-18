# Insights / Claude coordination - 15 October 2026

Scope: Insights into Company only. Trading module and scripts/cpanel-deploy.sh are owned by the concurrent Claude work and were not edited here.

Added VendorAssessment (read-only), RatingReportParser, vendor-assessment Blade partial and targeted regression tests. Controller passes the assessment and accepts RATING REPORT through the existing owner-only filing upload. Existing page gains a Vendor checks anchor/section with the existing styles and access boundary.

A scoped vendor_observations migration and owner-only history routes were subsequently added. No seeder, public API, dependency, trading or deployment changes. No production database imports performed. No automatic loan/vendor approval and no fabricated agency score.

Verification rules:
- CIN mismatch stops the filing import before financial/registry writes. Previous mismatched imports are not silently deleted; review them separately.
- Rating reports are stored as unverified evidence with review status, never passed to AOC-4 parsing. Match subject in heading, not a related company in the rationale. Manually check issuer, agency source, facility, date, instrument, action, outlook and latest publication.
- Historical rating tables are not selected as current ratings. Withdrawal in the summary is flagged; it is not treated as a default/fraud finding.
- Standalone statements only for vendor metrics. Missing financial inputs remain unknown; missing debt components are not assumed zero. EBIT interest coverage is explicitly labelled. DSCR requires cash available and a debt-service schedule, currently unavailable.
- Adjacent financial years only for YoY. Past growth is not a future forecast. 18 months is an internal freshness warning threshold, not a statutory rule.
- GST compliance, default history, legal/fraud, beneficiary checks and credit terms remain Not verified until actual evidence is available. A workflow checklist is provided; this is not a completed external verification integration or an approval workflow.

Supplied evidence inspected:
- document.pdf: ICRA report, DRAS Engineering Private Limited, 2 December 2025. Summary contains facility-specific upgraded ratings and historical values; do not flatten into one undated company rating.
- 202309130950_GNA_Drivelines_Limited.pdf: CARE report dated 26 September 2023 (use document date, not filename). Long and short term facility ratings withdrawn. Older BB+/A4 appear in history, not current ratings.

Run: php artisan test --filter=VendorAssessmentTest
Deploy through existing cPanel pipeline after reviewing the combined work with Claude. Do not replace the live custom public_html/index.php or overwrite Claude's deployment changes.

Remaining data work: attach authentic AOC-4/MGT-7 documents for the intended vendor, latest agency source evidence and cash-flow/debt/invoice schedules. PDF text extraction can be imperfect; review the original. No claim of production deployment or browser visual testing is made.

Added an explicitly labelled revenue stress scenario (default -10%, adjustable -100% to +100%). Operating costs and finance cost held constant; no cash-flow/payment prediction. Existing page GET validates inputs. Rating-style documents selected as AOC-4 are rejected for reclassification. Tests cover these cases as well.

## Dashboard and no-history workflow follow-up
User confirmed: no invoice/payment history available.
- InsightsDashboard: director tenure and disclosed ownership categories; unknown holdings stay unknown.
- Financial chart partial: signed revenue/profit and margins, missing points excluded, chart data table.
- Owner screen /admin/insights/vendors: up to 10 selected companies, exact material/currency/relationship filters, manual entry and transactional CSV import (500 rows maximum). References unique per company and relationship; duplicates reject the batch.
- VendorPerformance includes open overdue obligations in on-time denominator. Payment, refund and supplier delivery are separate contexts. Supplier quality is separately recorded.
- PaymentDelayModel: experimental 5-neighbour model, minimum 60 completed records across 5 companies; at least 40 training and 15 holdout records. Training outcomes must predate holdout origin. Reports MAE against training-median baseline. Not trained/validated on real data; no live predictions or default probability shown. Completed-outcome selection bias remains a limitation; do not use as automatic credit approval.
- Current workflow is append-only: already-recorded open obligations need a future audited settlement/correction workflow. Do not duplicate or replace them to simulate settlement.
- No trained production ML recommendation system or external verification API is claimed.
- Run targeted tests: php artisan test --filter="VendorWorkflowTest|VendorAssessmentTest"
- 15 tests passed using isolated SQLite where needed. Browser visual review remains unavailable.
- Deployment requires the new migration in the active Laravel root:
  php artisan migrate --path=database/migrations/2026_10_15_000010_create_vendor_observations_table.php --force
  Then clear/rebuild Blade cache through the established deploy pipeline.
- Do not run migrate:fresh or replace public_html/index.php. No live deployment was performed here.
- A concurrent process committed shared changes as 4f94db3 during verification; this agent did not create/push that commit.

Remaining before declaring the whole requested module finished: real filing review, audited correction/settlement workflow, validated recommendations with representative history, end-to-end browser review and cPanel deployment verification.