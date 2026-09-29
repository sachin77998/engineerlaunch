<?php
return [
    'timezone' => env('VISITOR_ANALYTICS_TIMEZONE', 'Asia/Kolkata'),
    'geolocation_enabled' => env('VISITOR_GEOLOCATION_ENABLED', true),
    'dbip_key' => env('VISITOR_DBIP_KEY', 'free'),
    'daily_lookup_limit' => (int) env('VISITOR_GEO_DAILY_LIMIT', 450),
];
