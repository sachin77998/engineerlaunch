#!/bin/bash
# Post-copy deployment steps for GoDaddy cPanel (.cpanel.yml).
# Every step runs even if an earlier one fails, and all output goes to storage/logs/deploy.log
# (readable by the owner at /admin/deploy-log and on /admin/maintenance).

APP="${1:?usage: cpanel-deploy.sh /path/to/laravel [seed]}"
MODE="${2:-deploy}"
LOG="$APP/storage/logs/deploy.log"
mkdir -p "$APP/storage/logs"
if [ "$MODE" = "deploy" ]; then echo "Deployment started $(date '+%Y-%m-%d %H:%M:%S')" > "$LOG"; fi

# The PHP CLI path differs between hosts; use the first one that exists (newest version first).
PHP="${PHP_BIN:-}"
if [ -z "$PHP" ]; then
    for candidate in /opt/cpanel/ea-php84/root/usr/bin/php /opt/cpanel/ea-php83/root/usr/bin/php /opt/cpanel/ea-php82/root/usr/bin/php \
                     /opt/cpanel/ea-php81/root/usr/bin/php /opt/alt/php83/usr/bin/php /opt/alt/php82/usr/bin/php /opt/alt/php81/usr/bin/php \
                     /usr/local/bin/php /usr/bin/php "$(command -v php 2>/dev/null)"; do
        if [ -n "$candidate" ] && [ -x "$candidate" ]; then PHP="$candidate"; break; fi
    done
fi
if [ -z "$PHP" ]; then
    echo "No PHP CLI binary found. Set PHP_BIN or run the steps from /admin/maintenance." >> "$LOG"
    exit 0
fi
echo "Using PHP: $PHP ($("$PHP" -r 'echo PHP_VERSION;' 2>&1))" >> "$LOG"

run() {
    echo "" >> "$LOG"
    echo "== $(date '+%H:%M:%S') php artisan $*" >> "$LOG"
    "$PHP" "$APP/artisan" "$@" >> "$LOG" 2>&1
    echo "-- exit code $?" >> "$LOG"
}

if [ "$MODE" = "deploy" ]; then
    run optimize:clear
    run env:ensure
    run optimize:clear
    run portal:prepare-discovery
    run migrate --force
    run optimize:clear
    # Seeding takes minutes to hours. Run it detached so this cPanel deployment finishes in seconds;
    # a deployment that is still "in progress" keeps cPanel's "Deploy HEAD Commit" button disabled.
    echo "" >> "$LOG"
    echo "Code deployed $(date '+%Y-%m-%d %H:%M:%S'); seeding continues in the background (see below)." >> "$LOG"
    nohup /bin/bash "$0" "$APP" seed > /dev/null 2>&1 < /dev/null &
    exit 0
fi

echo "" >> "$LOG"
echo "Background seeding started $(date '+%Y-%m-%d %H:%M:%S')" >> "$LOG"
run db:seed --class=TechnologySeeder --force
run db:seed --class=OwnerAccountSeeder --force
run db:seed --force
# Run explicitly too, in case an earlier seeder in DatabaseSeeder failed.
run db:seed --class=SectorCatalogSeeder --force
run db:seed --class=ItCompanyDirectorySeeder --force
run technologies:sync --skip-discovery
run discovery:bootstrap
run recommendations:build-skill-graph
run jobs:role-counts
# Market data for Do Trading (funds, ETFs, stocks, holidays, margins) and the company registers (queued).
run trading:sync
run insights:import-nse
run insights:import-mca
run insights:import-us
run market:data metal-history
run db:seed --class=InsurerSeeder --force
run sectors:file-listed
# Credit methodology tables and the first stored assessments (the nightly job keeps them current).
run insights:assess --seed-methodology
run insights:assess --limit=500
# AMC monthly portfolios and fund credit quality (also refreshed on the 12th and 20th of each month).
run mf:portfolios
run optimize:clear

echo "" >> "$LOG"
echo "Background seeding finished $(date '+%Y-%m-%d %H:%M:%S')" >> "$LOG"
exit 0
