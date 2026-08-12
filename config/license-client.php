<?php

declare(strict_types=1);

return [
    'base_url' => env('FINATTO_KEY_SERVICE_URL', 'https://key-service.memphislab.com.br'),
    'license_url' => env('FINATTO_KEY_SERVICE_LICENSE_URL', 'https://key-service.memphislab.com.br:8443'),
    'issuer' => env('FINATTO_LICENSE_ISSUER', 'key-service'),
    'timeout' => (int) env('FINATTO_LICENSE_TIMEOUT', 10),
    'connect_timeout' => (int) env('FINATTO_LICENSE_CONNECT_TIMEOUT', 5),
    'openssl_config' => env('FINATTO_LICENSE_OPENSSL_CONFIG'),
    'credentials' => ['path' => env('FINATTO_LICENSE_PATH', storage_path('app/finatto-license'))],
    'cache' => [
        'store' => env('FINATTO_LICENSE_CACHE_STORE'),
        'key' => env('FINATTO_LICENSE_CACHE_KEY', 'finatto:license:snapshot'),
        'ttl' => (int) env('FINATTO_LICENSE_CACHE_TTL', 300),
    ],
];
