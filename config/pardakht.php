<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment gateway that will be used
    | for payment transactions. You may set this to any of the gateways
    | defined in the "gateways" array.
    |
    */
    'default' => env('PARDAKHT_DEFAULT_GATEWAY', 'mellat'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many payment gateways as you wish. You
    | may use the same driver for multiple gateways or different drivers
    | for different gateways depending on your application's needs.
    |
    */
    'gateways' => [
        'mellat' => [
            'driver' => 'mellat',
            // For sandbox mode, use test credentials from https://banktest.ir
            'terminal_id' => env('MELLAT_TERMINAL_ID'),
            'username' => env('MELLAT_USERNAME'),
            'password' => env('MELLAT_PASSWORD'),
            'callback_url' => env('MELLAT_CALLBACK_URL'),
            'sandbox' => env('MELLAT_SANDBOX', false),
        ],

        'mabna' => [
            'driver' => 'mabna',
            'terminal_id' => env('MABNA_TERMINAL_ID'),
            'merchant_id' => env('MABNA_MERCHANT_ID'),
            'password' => env('MABNA_PASSWORD'),
            'callback_url' => env('MABNA_CALLBACK_URL'),
            'sandbox' => env('MABNA_SANDBOX', false),
        ],

        'zarinpal' => [
            'driver' => 'zarinpal',
            'merchant_id' => env('ZARINPAL_MERCHANT_ID'),
            'callback_url' => env('ZARINPAL_CALLBACK_URL'),
            'sandbox' => env('ZARINPAL_SANDBOX', false),
            'description' => env('ZARINPAL_DESCRIPTION', 'Payment via ZarinPal'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Storage
    |--------------------------------------------------------------------------
    |
    | Determines whether transactions should be stored in database.
    |
    */
    'store_transactions' => env('PARDAKHT_STORE_TRANSACTIONS', true),

    /*
    |--------------------------------------------------------------------------
    | Transaction Table Name
    |--------------------------------------------------------------------------
    |
    | The name of the database table where transactions will be stored.
    |
    */
    'transaction_table' => 'pardakht_transactions',

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | The default currency for transactions (IRR = Iranian Rial, IRT = Iranian Toman)
    |
    */
    'currency' => env('PARDAKHT_CURRENCY', 'IRR'),
];
