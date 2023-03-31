<?php

return [
    /**
     * ==========================================
     * Set Kredensial di file.env seperti berikut
     *         Atau set langsung di file ini
     * ==========================================
     *
     * Set true saat dalam Mode Development
     * Set false saat ke Mode Production
     */
    'isdev' => true,
    /**
     * Kredensial saat dalam Mode Development
     */

    'devcred' => [
        'merchantname' => env('FP_DEV_MERCHANT_NAME'),
        'merchantid' => env('FP_DEV_MERCHANT_ID'),
        'userid' => env('FP_DEV_USER_ID'),
        'password' => env('FP_DEV_PASSWORD'),
        'redirecturl' => env('FP_DEV_REDIRECT_URL')
    ],
    // Kredensial saat dalam Mode Production
    'prodcred' => [
        'merchantname' => env('FP_PROD_MERCHANT_NAME'),
        'merchantid' => env('FP_PROD_MERCHANT_ID'),
        'userid' => env('FP_PROD_USER_ID'),
        'password' => env('FP_PROD_PASSWORD'),
        'redirecturl' => env('FP_PROD_REDIRECT_URL')
    ],
];
