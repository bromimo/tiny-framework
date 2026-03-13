<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Настройки CORS
    |--------------------------------------------------------------------------
    |
    | allowed_origins — список доменов или '*' для всех.
    | allowed_methods — разрешённые HTTP-методы.
    | allowed_headers — разрешённые заголовки запроса.
    | max_age         — время кеширования preflight-ответа в секундах.
    |
    */

    'allowed_origins' => env('CORS_ALLOWED_ORIGINS', '*'),
    'allowed_methods' => 'GET, POST, PUT, DELETE, OPTIONS',
    'allowed_headers' => 'Content-Type, Authorization, X-Request-Id',
    'max_age'         => 86400,

];
