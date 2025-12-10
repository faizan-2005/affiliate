<?php

return [
    'name' => env('APP_NAME', 'Affiliate Tracking Panel'),
    'env' => env('APP_ENV', 'development'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost:8000'),
    'timezone' => 'UTC',

    'providers' => [
        'auth',
        'database',
        'redis',
        'cache',
    ],

    'aliases' => [
        'DB' => \App\Core\Database::class,
        'Cache' => \App\Core\Cache::class,
        'Session' => \App\Core\Session::class,
        'Queue' => \App\Core\Queue::class,
    ],

    'roles' => [
        'admin' => 'Administrator',
        'affiliate' => 'Affiliate',
        'advertiser' => 'Advertiser',
    ],

    'pagination' => [
        'per_page' => 50,
    ],

    'log' => [
        'channel' => 'single',
        'path' => storage_path('logs/app.log'),
        'level' => env('LOG_LEVEL', 'info'),
    ],

    'fraud' => [
        'enabled' => env('FRAUD_CHECK_ENABLED', true),
        'duplicate_threshold' => env('FRAUD_DUPLICATE_THRESHOLD', 3),
        'rate_limit' => env('FRAUD_RATE_LIMIT', 100),
        'checks' => [
            'duplicate_click' => true,
            'invalid_geo' => true,
            'bot_traffic' => true,
            'fast_clicks' => true,
            'blacklisted_ips' => true,
        ],
    ],

    'postback' => [
        'timeout' => env('POSTBACK_TIMEOUT', 30),
        'retries' => env('POSTBACK_RETRIES', 3),
        'require_signature' => true,
    ],

    'payout' => [
        'min_amount' => env('PAYOUT_MIN_AMOUNT', 10),
        'default_method' => env('PAYOUT_METHOD_DEFAULT', 'bank_transfer'),
        'methods' => [
            'bank_transfer' => 'Bank Transfer',
            'paypal' => 'PayPal',
            'wire' => 'Wire Transfer',
            'check' => 'Check',
        ],
    ],

    'storage' => [
        'hot_days' => 30,
        'archive_batch_size' => 10000,
    ],
];
