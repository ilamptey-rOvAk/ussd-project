<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Nalo Solutions USSD Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for Nalo Solutions USSD API gateway.
    |
    */

    'user_id' => env('NALO_USER_ID', 'AIK12345'),

    'shortcode' => env('USSD_SHORTCODE', '*920*29#'),

    'feelings' => [
        '1' => 'fine',
        '2' => 'sad',
        '3' => 'frisky',
    ],

    'reasons' => [
        '1' => 'relationship issues',
        '2' => 'money problems',
        '3' => 'family issues',
    ],
];
