<?php

return [
    'default' => env('REDIS_CACHE', 'default'),

    'connections' => [
        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD', null),
            'database' => env('REDIS_DB', 0),
            'timeout' => 0,
            'read_timeout' => 0,
        ],

        'cache' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD', null),
            'database' => env('REDIS_CACHE_DB', 1),
        ],

        'queue' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD', null),
            'database' => env('REDIS_QUEUE_DB', 2),
        ],
    ],

    'options' => [
        'cluster' => env('REDIS_CLUSTER', false),
        'prefix' => env('REDIS_PREFIX', 'affiliate:'),
    ],
];
