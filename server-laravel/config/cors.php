<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // 'paths' => ['api/*', 'sanctum/csrf-cookie'],
    // 'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'register'],
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // 'allowed_origins' => ['*'],

    // 'allowed_origins' => [

    //     'http://127.0.0.1:5173',
    //     'http://localhost:5173',
    //     '127.0.0.1:5173'
    // ],

    'allowed_origins' => [
        'http://127.0.0.1:5173',
        'http://localhost:5173',
        'http://172.17.150.248:5173',
    ],

    'allowed_origins_patterns' => [
        '/^http:\/\/172\.\d+\.\d+\.\d+:\d+$/',   // Allow all 172.x.x.x IPs
        '/^http:\/\/192\.168\.\d+\.\d+:\d+$/',  // Allow all 192.168.x.x IPs
        '/^http:\/\/10\.\d+\.\d+\.\d+:\d+$/',   // Allow all 10.x.x.x IPs
    ],

    'allowed_headers' => ['*'],

    // 'exposed_headers' => [],

    'exposed_headers' => ['Authorization'],

    'max_age' => 0,

    // 'supports_credentials' => false,

    'supports_credentials' => true,

];
