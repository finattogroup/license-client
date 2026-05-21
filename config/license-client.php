<?php

declare(strict_types=1);

return [
    'base_url' => env('LICENSE_MANAGER_URL', 'https://license.test'),

    'http' => [
        'timeout' => (int) env('LICENSE_HTTP_TIMEOUT', 10),
        'retry_times' => (int) env('LICENSE_HTTP_RETRY_TIMES', 2),
        'retry_sleep' => (int) env('LICENSE_HTTP_RETRY_SLEEP', 200),
    ],

    'cache' => [
        'store' => env('LICENSE_CACHE_STORE'),
        'prefix' => env('LICENSE_CACHE_PREFIX', 'finatto:license'),
        'token_leeway' => (int) env('LICENSE_TOKEN_LEEWAY', 60),
        'snapshot_ttl' => (int) env('LICENSE_SNAPSHOT_TTL', 300),
    ],
];
