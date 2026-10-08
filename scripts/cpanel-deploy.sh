#!/bin/bash
# Post-copy deployment steps for GoDaddy cPanel (.cpanel.yml).
# Every step runs even if an earlier one fails, and all output goes to storage/logs/deploy.log
# (readable by the owner at /admin/deploy-log), so one failing step can no longer silently skip the rest.

APP="${1:?usage: cpanel-deploy.sh /path/to/laravel}"
PHP="${PHP_BIN:-/opt/cpanel/ea-php83/root/usr/bin/php}"
LOG="$APP/storage/logs/deploy.log"
mkdir -p "$APP/storage/logs"
echo "Deployment started $(date '+%Y-%m-%d %H:%M:%S')" > "$LOG"

run() {
    echo "" >> "$LOG"
    echo "== $(date '+%H:%M:%S') php artisan $*" >> "$LOG"
    "$PHP" "$APP/artisan" "$@" >> "$LOG" 2>&1
    echo "-- exit code $?" >> "$LOG"
}

run optimize:clear
run env:ensure
run optimize:clear
run portal:prepare-discovery
run migrate --force
run migrate:status
run db:seed --class=TechnologySeeder --force
run db:seed --class=OwnerAccountSeeder --force
run db:seed --force
# Run explicitly too, in case an earlier seeder in DatabaseSeeder failed.
run db:seed --class=SectorCatalogSeeder --force
run optimize:clear

echo "" >> "$LOG"
echo "Deployment finished $(date '+%Y-%m-%d %H:%M:%S')" >> "$LOG"
exit 0
