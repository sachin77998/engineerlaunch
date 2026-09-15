<?php

return [
    'enabled' => (bool) env('KAFKA_ENABLED', false),
    'brokers' => env('KAFKA_BROKERS', '127.0.0.1:9092'),
    'client_id' => env('KAFKA_CLIENT_ID', env('APP_NAME', 'ascendia')),
    'group_id' => env('KAFKA_GROUP_ID', 'ascendia-workers'),
    'security_protocol' => env('KAFKA_SECURITY_PROTOCOL', 'plaintext'),
    'sasl_mechanism' => env('KAFKA_SASL_MECHANISM'),
    'sasl_username' => env('KAFKA_SASL_USERNAME'),
    'sasl_password' => env('KAFKA_SASL_PASSWORD'),
    'flush_timeout_ms' => (int) env('KAFKA_FLUSH_TIMEOUT_MS', 1500),
    'topics' => [
        'job_ingestion' => env('KAFKA_TOPIC_JOB_INGESTION', 'ascendia.jobs.ingestion.v1'),
        'email_dispatch' => env('KAFKA_TOPIC_EMAIL_DISPATCH', 'ascendia.email.dispatch.v1'),
        'profile_audit' => env('KAFKA_TOPIC_PROFILE_AUDIT', 'ascendia.profile.audit.v1'),
        'news_events' => env('KAFKA_TOPIC_NEWS_EVENTS', 'ascendia.news.events.v1'),
    ],
];
