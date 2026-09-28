# Job search and official-feed release

The changes and imports are prepared locally. The live site has not been updated. Confirm the actual application directory in File Manager; the path below is the existing deployment path from the project.

## Apply with File Manager and phpMyAdmin

1. In File Manager, find the application's .env and confirm its DB_DATABASE matches the database selected in phpMyAdmin. Export that database before importing this update.
2. Upload website-update.zip into /home/YOUR_CPANEL_USERNAME/career-portal/naukri-com/ and extract there, replacing matching files. This is the Laravel application directory, not its public/ subdirectory. The archive contains code only, with no .env, user data, or credentials.
3. In phpMyAdmin, choose that database, select Import, and import career-explorer.sql.gz first, then jobs-and-skills.sql.gz. The first creates the career tables and password reset token table and imports the career catalog. The second imports jobs. It contains current openings from six official Greenhouse feeds, company records, and searchable technology links. It can be re-imported without creating duplicate jobs. It does not remove unrelated jobs or users.
4. If present, remove only bootstrap/cache/config.php and bootstrap/cache/routes-v7.php through File Manager so Laravel reloads the new configuration and routes. Keep all other files.
5. Reload the homepage with Ctrl+F5. Check C++, Java, Python, Node JS and Springboot. Click statistic cards, job titles, skill tags, and the DLF Cyber City link.

The snapshot is dated in verification.json. It supplies roughly 1,743 current roles across all locations, not a guarantee that every requested skill has a current vacancy.

## New pages and email setup

Open /career-explorer for sidebar filters, sourced factory/company profiles, real openings and separate department career tracks. Open /learn and /learn/php-laravel to see topics directly; study guidance is collapsed in the right sidebar.

The career import has 33 tracks, 199 roles and 66 user-provided indicative salary references. Salary references are labeled estimates and are separate from advertised pay. Company-wide employee numbers are not vacancy counts. Google URLs create a discovery search; they do not act as a jobs API. Only registered supported official sources can be refreshed.

Forgot Password is at /forgot-password, linked from each login page. Set APP_URL to the site's actual base URL and configure MAIL_MAILER=smtp, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION and MAIL_FROM_ADDRESS in the live .env using the host's mail settings. Do not share those credentials. Clear bootstrap/cache/config.php after changing .env. Verify delivery by requesting a reset for an existing account and opening the email. Production log/array mailers are rejected because they do not deliver mail. A reset cannot create an absent account; use the separate owner recovery import only if needed.

The SQL creates tables directly for File Manager users. The guarded migration remains safe if migrations are run later. No Composer or frontend build is required.

## Keep jobs fresh using cPanel Cron Jobs

If cPanel provides Cron Jobs, add this command to run daily (for example 02:00):

    /opt/cpanel/ea-php83/root/usr/bin/php /home/YOUR_CPANEL_USERNAME/career-portal/naukri-com/artisan jobs:refresh-discovery --all >> /home/YOUR_CPANEL_USERNAME/career-portal/naukri-com/storage/logs/discovery-refresh.log 2>&1

It seeds skills and company sources, synchronizes enabled feeds, and backfills searchable skills. A lock prevents overlapping instances. It runs directly without a queue worker. Large company feeds can take substantial time; the host must allow the cron process to finish. Inspect the log for failures. Without scheduled refreshes, the SQL import remains a dated snapshot.

The default command without --all refreshes only the six added feeds. --prepare-only updates directories and skills without network fetching.

Companies without a supported working feed remain discoverable through official career links. No vacancies are fabricated. Postman's old Greenhouse feed currently returns 404 and is disabled until its replacement integration is verified.

## Missing owner account

The separate owner-account-recovery.sql creates the configured owner only if its email is absent. Run it only in the verified live database if the earlier empty-account result still applies. It uses the password supplied in this conversation and leaves any existing account untouched. Keep this SQL file off the public website.

The updated OwnerAccountSeeder preserves existing passwords. If Terminal becomes available, php artisan owner:reset-password provides hidden password prompts.

## Verification and source evidence

Regression tests cover requested skill aliases, C/C++ and Java/JavaScript distinctions, description extraction, partial-feed handling, clickable homepage destinations, the Cyber City directory, and password preservation.

The Cyber City page cites each company's official office/careers page. MongoDB, Expedia Group, Dynata, Gartner and Nokia are listed; individual vacancies may have different workplaces. See config/cyber_city.php and verification.json for source URLs.

A connected browser and production database session were unavailable, so live deployment and visual browser verification remain outstanding.

## cPanel Git deployment path correction

The deployment configuration now uses the cPanel HOME directory instead of a hard-coded account name. public_html/index.php must retain its existing ../career-portal/naukri-com paths. CSS, JavaScript and images are copied to public_html separately; its index.php and .htaccess are preserved. Replace YOUR_CPANEL_USERNAME in the examples above with the home-directory name shown in File Manager.
