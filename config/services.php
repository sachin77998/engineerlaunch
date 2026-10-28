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
        'providers' => env('INSIGHTS_PROVIDERS', 'sandbox,surepass,instafinancials,rapidapi'),
        // Fetch automatically on search misses and for company pages without financials (each call is billed).
        'auto' => env('INSIGHTS_PROVIDERS_AUTO', true),
    ],
    'instafinancials' => [
        'base_url' => env('INSTAFINANCIALS_BASE_URL', 'https://api.instafinancials.com'),
        'api_key' => env('INSTAFINANCIALS_API_KEY'),
        'summary_path' => env('INSTAFINANCIALS_SUMMARY_PATH'),
    ],
    // Sandbox (sandbox.co.in): POST /authenticate with key + secret gives a 24-hour token for the MCA/GST APIs.
    'sandbox' => [
        'base_url' => env('SANDBOX_BASE_URL', 'https://api.sandbox.co.in'),
        'api_key' => env('SANDBOX_API_KEY'),
        'api_secret' => env('SANDBOX_API_SECRET'),
        'api_version' => env('SANDBOX_API_VERSION', '1.0'),
        'paths' => [
            'auth' => env('SANDBOX_AUTH_PATH', '/authenticate'),
            'company' => env('SANDBOX_COMPANY_PATH', '/kyc/mca/company/master-data/search'),
            'director' => env('SANDBOX_DIRECTOR_PATH', '/kyc/mca/director/master-data/search'),
            'gstin' => env('SANDBOX_GSTIN_PATH', '/gst/compliance/public/gstin/search'),
        ],
    ],
    // Optional company-logo source (logo.dev publishable key); without it logos come from each site and favicon services.
    'logo_dev' => ['token' => env('LOGO_DEV_TOKEN')],
    'surepass' => [
        'base_url' => env('SUREPASS_BASE_URL', 'https://kyc-api.surepass.io/api/v1'),
        'token' => env('SUREPASS_TOKEN'),
        'paths' => [
            'company' => env('SUREPASS_COMPANY_PATH', 'corporate/company-details'),
            'gstin' => env('SUREPASS_GSTIN_PATH', 'corporate/gstin'),
            'din' => env('SUREPASS_DIN_PATH', 'corporate/din'),
            'search' => env('SUREPASS_SEARCH_PATH'),
            // Candidate verification (hiring suite).
            'pan' => env('SUREPASS_PAN_PATH', 'pan/pan'),
            'aadhaar_otp' => env('SUREPASS_AADHAAR_OTP_PATH', 'aadhaar-v2/generate-otp'),
            'aadhaar_submit' => env('SUREPASS_AADHAAR_SUBMIT_PATH', 'aadhaar-v2/submit-otp'),
            'uan' => env('SUREPASS_UAN_PATH', 'income/employment-history-uan'),
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

    // SMS OTP for mobile verification (MSG91 OTP API: auth key and a DLT-approved OTP template).
    'msg91' => [
        'key' => env('MSG91_AUTH_KEY'),
        'otp_template' => env('MSG91_OTP_TEMPLATE_ID'),
    ],

    // Zerodha Kite Connect: each investor logs in with their own Zerodha account. Set the redirect URL in the Kite
    // developer console to {APP_URL}/trading/broker/kite/callback and the postback URL to {APP_URL}/trading/broker/kite/postback.
    'kite' => [
        'api_key' => env('KITE_API_KEY'),
        'api_secret' => env('KITE_API_SECRET'),
    ],

    // Groww Trade API (the owner's own account): read-only market data. Key and secret live only in .env.
    'groww' => [
        'base_url' => env('GROWW_BASE_URL', 'https://api.groww.in'),
        'api_key' => env('GROWW_API_KEY'),
        'api_secret' => env('GROWW_API_SECRET'),
    ],

    // Gold and silver spot prices (free key at goldapi.io).
    'goldapi' => ['key' => env('GOLDAPI_KEY')],

    'jooble' => [
        'key' => env('JOOBLE_API_KEY'),
        'host' => env('JOOBLE_HOST', 'jooble.org'),
        'country' => env('JOOBLE_COUNTRY', 'United States'),
    ],


    // SEC EDGAR asks every client to identify itself with a contact email (fair-access policy).
    'sec' => [
        'user_agent' => env('SEC_USER_AGENT', 'Ascendia Insights sachinsoni77998@gmail.com'),
    ],

    // Paytm Payment Gateway (Paytm for Business → Developers → API keys). Staging: PAYTM_ENV=staging, PAYTM_WEBSITE=WEBSTAGING.
    'paytm' => [
        'environment' => env('PAYTM_ENV', 'staging'),
        'mid' => env('PAYTM_MID'),
        'merchant_key' => env('PAYTM_MERCHANT_KEY'),
        'website' => env('PAYTM_WEBSITE', 'WEBSTAGING'),
        'callback_url' => env('PAYTM_CALLBACK_URL'),
    ],
];
