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

];
