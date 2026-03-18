<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Default Reverb Server
     |--------------------------------------------------------------------------
     |
     | This option controls the default server used by Reverb to handle
     | incoming messages as well as broadcasting events to all your
     | connected clients. The "reverb" server is used by default.
     |
     */

    'default' => env('REVERB_SERVER', 'reverb'),

    /*
     |--------------------------------------------------------------------------
     | Reverb Servers
     |--------------------------------------------------------------------------
     |
     | Here you may define all of the Reverb servers used by your application.
     | Each server has its own configuration options, including the host
     | and port it will listen on and the applications it supports.
     |
     */

    'servers' => [

        'reverb' => [
            'host' => env('REVERB_SERVER_HOST', '0.0.0.0'),
            'port' => env('REVERB_SERVER_PORT', 8080),
            'hostname' => env('REVERB_HOST'),
            'options' => [
                'tls' => [],
            ],
            'max_request_size' => env('REVERB_MAX_REQUEST_SIZE', 10000),
            'scaling' => [
                'enabled' => env('REVERB_SCALING_ENABLED', false),
                'channel' => env('REVERB_SCALING_CHANNEL', 'reverb'),
                'server' => [
                    'host' => env('REDIS_HOST', '127.0.0.1'),
                    'port' => env('REDIS_PORT', '6379'),
                    'username' => env('REDIS_USERNAME'),
                    'password' => env('REDIS_PASSWORD'),
                    'database' => env('REDIS_DB', '0'),
                ],
            ],
            'pulse_ingest_interval' => env('REVERB_PULSE_INGEST_INTERVAL', 15),
            'telescope_ingest_interval' => env('REVERB_TELESCOPE_INGEST_INTERVAL', 15),
        ],

    ],

    /*
     |--------------------------------------------------------------------------
     | Reverb Applications
     |--------------------------------------------------------------------------
     |
     | Here you may define all of the applications used by your Reverb server.
     | Each application must have a unique "id" and "key". You may also
     | specify the "secret" and "host" for each Reverb application.
     |
     */

    'apps' => [
        'provider' => 'config',

        'apps' => [
            [
                'key' => env('REVERB_APP_KEY'),
                'secret' => env('REVERB_APP_SECRET'),
                'app_id' => env('REVERB_APP_ID'),
                'options' => [
                    'host' => null,
                    'port' => env('REVERB_PORT', 443),
                    'scheme' => env('REVERB_SCHEME', 'https'),
                    'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
                ],
                'allowed_origins' => ['*'],
                'ping_interval' => env('REVERB_APP_PING_INTERVAL', 60),
                'activity_timeout' => env('REVERB_APP_ACTIVITY_TIMEOUT', 30),
                'max_message_size' => env('REVERB_APP_MAX_MESSAGE_SIZE', 10000),
            ],
        ],
    ],

];
