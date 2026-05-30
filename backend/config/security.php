<?php

return [
    'enforce_https' => env('SECURITY_ENFORCE_HTTPS', env('APP_ENV') !== 'local'),
    'invite_only' => env('SECURITY_INVITE_ONLY', false),

    'trusted_proxies' => env('SECURITY_TRUSTED_PROXIES', '*'),

    'contract_signing_link_ttl_hours' => (int) env('SECURITY_CONTRACT_SIGNING_LINK_TTL_HOURS', 168),
    'invoice_payment_link_ttl_hours' => (int) env('SECURITY_INVOICE_PAYMENT_LINK_TTL_HOURS', 168),

    'redis_required_in_production' => env('SECURITY_REDIS_REQUIRED_IN_PRODUCTION', true),
    'row_level_security_required' => env('SECURITY_ROW_LEVEL_SECURITY_REQUIRED', true),

    'api_gateway_enforced' => env('SECURITY_API_GATEWAY_ENFORCED', false),
    'api_gateway_shared_secret' => env('API_GATEWAY_SHARED_SECRET'),
    'api_gateway_require_request_id' => env('SECURITY_API_GATEWAY_REQUIRE_REQUEST_ID', true),
    'api_gateway_allowed_hosts' => array_values(array_filter(array_map(
        static fn (string $host) => trim($host),
        explode(',', (string) env('SECURITY_API_GATEWAY_ALLOWED_HOSTS', ''))
    ))),

    'suspicious_requests_per_minute' => (int) env('SECURITY_SUSPICIOUS_REQUESTS_PER_MINUTE', 120),
    'suspicious_failures_per_minute' => (int) env('SECURITY_SUSPICIOUS_FAILURES_PER_MINUTE', 25),
    'waf_block_duration_minutes' => (int) env('SECURITY_WAF_BLOCK_DURATION_MINUTES', 10),
    'telemetry_event_limit_per_minute' => (int) env('SECURITY_TELEMETRY_EVENT_LIMIT_PER_MINUTE', 120),
    'frontend_error_limit_per_minute' => (int) env('SECURITY_FRONTEND_ERROR_LIMIT_PER_MINUTE', 30),
    'paid_api_requests_per_minute' => (int) env('SECURITY_PAID_API_REQUESTS_PER_MINUTE', 8),
    'paid_api_requests_per_hour' => (int) env('SECURITY_PAID_API_REQUESTS_PER_HOUR', 40),
    'ai_prompt_user_delimiter_start' => env('SECURITY_AI_PROMPT_USER_DELIMITER_START', 'BEGIN USER CONTENT'),
    'ai_prompt_user_delimiter_end' => env('SECURITY_AI_PROMPT_USER_DELIMITER_END', 'END USER CONTENT'),

    'waf_signatures' => [
        'union select',
        '<script',
        '../',
        '%00',
        'or 1=1',
        'sleep(',
        'benchmark(',
        'information_schema',
        'load_file(',
    ],

    'scan_path_fragments' => [
        '.env',
        'wp-admin',
        'wp-login',
        'xmlrpc.php',
        'phpmyadmin',
        'boaform',
        'vendor/phpunit',
        'setup.cgi',
    ],
];
