<?php

return [
    'vapid' => [
        'subject'     => env('APP_URL', 'https://feer.org.pl'),
        'public_key'  => env('VAPID_PUBLIC_KEY', ''),
        'private_key' => env('VAPID_PRIVATE_KEY', ''),
    ],
];
