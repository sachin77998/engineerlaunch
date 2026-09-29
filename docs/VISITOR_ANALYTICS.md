# Admin visitor analytics

The /admin dashboard shows a selected day's unique browsers, page views,
state/country counts (including Unknown), and 30 daily totals. Defaults to today
in Asia/Kolkata; VISITOR_ANALYTICS_TIMEZONE can change the reporting timezone.
The existing admin/auth middleware protects the report.

Deploy through cPanel Update from Remote and Deploy HEAD Commit. The existing
deployment task runs the new daily_visitors migration. No queue worker or manual
SQL import is needed. Historical state counts cannot be reconstructed from hashed
IP addresses; this report starts collecting on deployment.

An encrypted, HTTP-only portal_visitor cookie identifies one browser for a year.
The database stores an HMAC visitor identifier, local reporting date, counters,
timestamps and approximate state/country. Repeated requests atomically increment
views; a unique date/visitor constraint prevents duplicate daily visitors.
Different browsers/devices or cleared cookies count separately. This is not a
verified count of people. Known bot user agents, admin/API/auth pages, non-HTML,
downloads and unsuccessful responses are excluded. Fully CDN-cached pages that
never reach Laravel cannot be counted by this server-side tracker.

State lookup runs on application termination after the response, over HTTPS to
DB-IP. Only the public IP is sent, not account or browsing details. The raw IP is
not stored by this tracker. Cached results use a hashed key, expire after seven
days, and failures expire after one hour. Update the site's privacy disclosure
to reflect the analytics cookie and IP geolocation provider.

Provider documentation: https://db-ip.com/api/doc.php
Free quota: https://db-ip.com/api/free (500 requests daily when implemented).
Default local budget is 450 requests per UTC day. Exceeded quota, timeouts,
private addresses and unavailable location data produce Unknown without stopping
visitor counts. Attribution is displayed beside the report.
VISITOR_DBIP_KEY supports a paid DB-IP key if traffic needs a larger allowance;
adjust VISITOR_GEO_DAILY_LIMIT to the purchased allowance. No paid account is
created automatically. VISITOR_GEOLOCATION_ENABLED=false disables lookups.

Uses REMOTE_ADDR rather than untrusted forwarded headers. On hosting behind a
reverse proxy/CDN, configure the web server to restore the real client address
from that trusted proxy; otherwise geolocation will describe the proxy.
Neither candidate profile state nor selected search state is used.

Validation:
php artisan test --filter=VisitorAnalyticsTest
