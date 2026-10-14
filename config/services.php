<?php

return [
    'job_ingestion' => [
        'key' => env('JOB_INGESTION_KEY'),
    ],
    'admin_email' => env('ADMIN_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'verification_otp' => [
        // The service additionally enforces that fixed OTPs can only run in
        // local/testing environments, preventing production misconfiguration.
        'driver' => env('VERIFICATION_OTP_DRIVER', env('APP_ENV') === 'local' ? 'testing' : 'mail'),
    ],

    'judge0' => [
        'url' => env('JUDGE0_URL', 'https://ce.judge0.com'),
        'token' => env('JUDGE0_TOKEN'),
        'languages' => [
            'c' => 50,
            'cpp' => 54,
            'java' => 62,
            'javascript' => 63,
            'php' => 68,
            'python' => 71,
            'ruby' => 72,
            'rust' => 73,
            'typescript' => 74,
            'go' => 60,
        ],
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'adzuna' => [
        'app_id' => env('ADZUNA_APP_ID'),
        'app_key' => env('ADZUNA_APP_KEY'),
    ],

    // Company insights data providers (paid; each switches on when its key is set).
    'insights' => [
        'providers' => env('INSIGHTS_PROVIDERS', 'surepass,instafinancials,rapidapi'),
        // Fetch automatically on search misses and for company pages without financials (each call is billed).
        'auto' => env('INSIGHTS_PROVIDERS_AUTO', true),
    ],
    'instafinancials' => [
        'base_url' => env('INSTAFINANCIALS_BASE_URL', 'https://instafinancials.com/api'),
        'api_key' => env('INSTAFINANCIALS_API_KEY'),
        'summary_path' => env('INSTAFINANCIALS_SUMMARY_PATH', '/InstaReports/v1/InstaSummary/CompanyCIN/{cin}'),
    ],
    'surepass' => [
        'base_url' => env('SUREPASS_BASE_URL', 'https://kyc-api.surepass.io/api/v1'),
        'token' => env('SUREPASS_TOKEN'),
        'paths' => [
            'company' => env('SUREPASS_COMPANY_PATH', 'corporate/company-details'),
            'gstin' => env('SUREPASS_GSTIN_PATH', 'corporate/gstin'),
            'din' => env('SUREPASS_DIN_PATH', 'corporate/din'),
            'search' => env('SUREPASS_SEARCH_PATH'),
        ],
    ],
    'rapidapi' => [
        'key' => env('RAPIDAPI_KEY'),
        'host' => env('RAPIDAPI_HOST'),
        // URL templates of the subscribed RapidAPI company-data API; {cin}, {gstin}, {din}, {name} are filled in.
        'urls' => [
            'company' => env('RAPIDAPI_COMPANY_URL'),
            'gstin' => env('RAPIDAPI_GSTIN_URL'),
            'din' => env('RAPIDAPI_DIN_URL'),
            'search' => env('RAPIDAPI_SEARCH_URL'),
        ],
    ],

    // MCA Company Master Data on data.gov.in (free key: data.gov.in → sign in → My Account → Generate API key).
    'datagov' => [
        'key' => env('DATAGOV_API_KEY'),
        'company_resources' => array_filter(explode(',', env('DATAGOV_COMPANY_RESOURCES', 'ec58dab7-d891-4abb-936e-d5d274a6ce9b'))),
    ],

    // Gold and silver spot prices (free key at goldapi.io).
    'goldapi' => ['key' => env('GOLDAPI_KEY')],

    'jooble' => [
        'key' => env('JOOBLE_API_KEY'),
        'host' => env('JOOBLE_HOST', 'jooble.org'),
        'country' => env('JOOBLE_COUNTRY', 'United States'),
    ],

];
