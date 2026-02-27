<?php

return [

    'broadcasting' => [

        /*
         * This is the Echo config that Filament injects into window.Echo.
         * It must match the Laravel Reverb settings in your .env.
         */
        'echo' => [
            'broadcaster'       => 'reverb',
            'key'               => env('VITE_REVERB_APP_KEY'),
            'wsHost'            => env('VITE_REVERB_HOST', '127.0.0.1'),
            'wsPort'            => (int) env('VITE_REVERB_PORT', 8080),
            'wssPort'           => (int) env('VITE_REVERB_PORT', 443),
            'forceTLS'          => env('VITE_REVERB_SCHEME', 'http') === 'https',
            'enabledTransports' => ['ws', 'wss'],
            'disableStats'      => true,
        ],

    ],

];
