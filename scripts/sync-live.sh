#!/bin/bash
# Run from the cPanel repository clone after .cpanel.yml has copied the code to DEPLOY_PATH.
# The live site runs whichever Laravel folder public_html/index.php loads. When that is not DEPLOY_PATH, a deploy
# never reaches the website; this copies the same code there and runs the deploy steps in that folder too.
# Everything it finds is printed (and appended to storage/logs/deploy.log of each folder it updates).

DEPLOY_PATH="${1:?usage: sync-live.sh DEPLOY_PATH}"
REPO="$(pwd)"
COMMIT="$(git log -1 --format='%h %cd %s' --date=format:'%Y-%m-%d %H:%M' 2>/dev/null)"
echo "Repository: $REPO"
echo "Deploying:  $COMMIT"
echo "Copied to:  $DEPLOY_PATH"

for DOCROOT in "$HOME/public_html" "$HOME/public_html/"*/; do
    INDEX="${DOCROOT%/}/index.php"
    [ -f "$INDEX" ] || continue
    REL="$(grep -o "__DIR__ *\. *'[^']*/vendor/autoload.php'" "$INDEX" | head -1 | sed "s#__DIR__ *\. *'##; s#/vendor/autoload.php'##")"
    [ -n "$REL" ] || continue
    LIVE="$(cd "${DOCROOT%/}/$REL" 2>/dev/null && pwd)"
    echo "Website $INDEX loads: ${LIVE:-$REL (missing)}"
    [ -n "$LIVE" ] && [ -f "$LIVE/artisan" ] || continue
    [ "$LIVE" = "$(cd "$DEPLOY_PATH" 2>/dev/null && pwd)" ] && { echo "  = DEPLOY_PATH, already updated"; continue; }

    echo "  differs from DEPLOY_PATH: updating it as well"
    for d in app bootstrap config database lang resources routes tests public docs scripts; do
        [ -e "$REPO/$d" ] && /bin/cp -R "$REPO/$d" "$LIVE/"
    done
    for f in artisan composer.json composer.lock package.json vite.config.js; do
        [ -e "$REPO/$f" ] && /bin/cp "$REPO/$f" "$LIVE/"
    done
    /bin/mkdir -p "$LIVE/storage/app" "$LIVE/storage/logs"
    echo "$COMMIT" > "$LIVE/storage/app/deployed-version.txt"
    # Stale caches would keep serving the old pages.
    rm -f "$LIVE"/bootstrap/cache/*.php "$LIVE"/storage/framework/views/*.php
    /bin/bash "$LIVE/scripts/cpanel-deploy.sh" "$LIVE"
    echo "  done: $LIVE"
done
