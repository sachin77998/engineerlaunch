#!/bin/bash
# Post-copy deployment steps for GoDaddy cPanel (.cpanel.yml).
# Every step runs even if an earlier one fails, and all output goes to storage/logs/deploy.log
# (readable by the owner at /admin/deploy-log and on /admin/maintenance).

APP="${1:?usage: cpanel-deploy.sh /path/to/laravel}"
LOG="$APP/storage/logs/deploy.log"
mkdir -p "$APP/storage/logs"
echo "Deployment started $(date '+%Y-%m-%d %H:%M:%S')" > "$LOG"

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

run optimize:clear
run env:ensure
run optimize:clear
run portal:prepare-discovery
run migrate --force
run db:seed --class=TechnologySeeder --force
run db:seed --class=OwnerAccountSeeder --force
run db:seed --force
# Run explicitly too, in case an earlier seeder in DatabaseSeeder failed.
run db:seed --class=SectorCatalogSeeder --force
run db:seed --class=ItCompanyDirectorySeeder --force
run optimize:clear

echo "" >> "$LOG"
echo "Deployment finished $(date '+%Y-%m-%d %H:%M:%S')" >> "$LOG"
exit 0
