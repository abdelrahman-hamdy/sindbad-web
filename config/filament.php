<?php

return [

    'broadcasting' => [

        /*
         * This is the Echo config that Filament injects into window.Echo.
         * It must match the Laravel Reverb settings in your .env.
         */
        'echo' => [
            'broadcaster'  => 'pusher',
            'key'          => env('PUSHER_APP_KEY'),
            'cluster'      => env('PUSHER_APP_CLUSTER', 'mt1'),
            'forceTLS'     => true,
            'encrypted'    => true,
            'disableStats' => true,
        ],

    ],

];
