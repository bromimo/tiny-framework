<?php

return [
    'defaults' => [
        'guard' => 'api',
    ],

    'guards' => [
        'api' => [
            'driver' => 'token',
        ],
        // 'web' => ['driver' => 'session'],
    ],

    'token' => [
        'lifetime' => env('AUTH_TOKEN_LIFETIME', '+28 days'),
    ],

    'password' => [
        'algo'    => PASSWORD_BCRYPT,
        'options' => [],
    ],

    'trusted_proxies' => array_filter(
        explode(',', env('TRUSTED_PROXIES', '')),
        fn(string $ip) => $ip !== ''
    ),

    'roles' => [
        'user' => [
            'description' => 'Пользователь',
            'permissions' => ['users.view', 'users.update'],
        ],
        'admin' => [
            'description' => 'Администратор',
            'permissions' => ['*'],
        ],
    ],

    'permissions' => [
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
    ],
];
