<?php
return [
    'driver' => env('SEARCH_DRIVER', 'database'),
    'index' => env('SEARCH_INDEX', 'jobs'),
    'elasticsearch_url' => env('ELASTICSEARCH_URL'),
    'elasticsearch_username' => env('ELASTICSEARCH_USERNAME'),
    'elasticsearch_password' => env('ELASTICSEARCH_PASSWORD'),
    'browser_cache_seconds' => (int) env('DISCOVERY_BROWSER_CACHE_SECONDS', 300),
    'shared_cache_seconds' => (int) env('DISCOVERY_CACHE_SECONDS', 3600),
    'results_per_page' => (int) env('JOB_RESULTS_PER_PAGE', 20),
    'max_results_per_page' => (int) env('JOB_MAX_RESULTS_PER_PAGE', 50),
];
