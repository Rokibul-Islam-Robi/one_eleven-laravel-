<?php

/*
| Payment gateway credentials — same BHERB_* environment variables as the
| original includes/config.php. Sandbox blocks start EMPTY, so with nothing
| configured the local offline demo gateway (payment/demo) is used.
| Mode: BHERB_PAYMENT_MODE = demo | sandbox | live (auto-detected when unset).
*/
$e = static function (string $key, string $default = ''): string {
    $v = env($key);
    return ($v === null || trim((string) $v) === '') ? $default : trim((string) $v);
};

return [
    'mode' => strtolower($e('BHERB_PAYMENT_MODE')),

    'gateways' => [
        'sslcommerz' => [
            'label' => 'SSLCommerz',
            'env' => [
                'sandbox' => [
                    'base'       => 'https://sandbox.sslcommerz.com',
                    'store_id'   => $e('BHERB_SSLCZ_STORE_ID'),
                    'store_pass' => $e('BHERB_SSLCZ_STORE_PASS'),
                ],
                'live' => [
                    'base'       => 'https://securepay.sslcommerz.com',
                    'store_id'   => $e('BHERB_SSLCZ_LIVE_STORE_ID'),
                    'store_pass' => $e('BHERB_SSLCZ_LIVE_STORE_PASS'),
                ],
            ],
        ],
        'bkash' => [
            'label' => 'bKash',
            'env' => [
                'sandbox' => [
                    'base'       => 'https://tokenized.sandbox.bka.sh/v1.2.0-beta',
                    'username'   => $e('BHERB_BKASH_USERNAME'),
                    'password'   => $e('BHERB_BKASH_PASSWORD'),
                    'app_key'    => $e('BHERB_BKASH_APP_KEY'),
                    'app_secret' => $e('BHERB_BKASH_APP_SECRET'),
                ],
                'live' => [
                    'base'       => 'https://tokenized.pay.bka.sh/v1.2.0-beta',
                    'username'   => $e('BHERB_BKASH_LIVE_USERNAME'),
                    'password'   => $e('BHERB_BKASH_LIVE_PASSWORD'),
                    'app_key'    => $e('BHERB_BKASH_LIVE_APP_KEY'),
                    'app_secret' => $e('BHERB_BKASH_LIVE_APP_SECRET'),
                ],
            ],
        ],
        'nagad' => [
            'label' => 'Nagad',
            'env' => [
                'sandbox' => [
                    'base'                 => 'http://sandbox.mynagad.com:10080/remote-payment-gateway-1.0/api/dfs/',
                    'merchant_id'          => $e('BHERB_NAGAD_MERCHANT_ID'),
                    'account_number'       => $e('BHERB_NAGAD_ACCOUNT_NUMBER'),
                    'currency_code'        => $e('BHERB_NAGAD_CURRENCY_CODE', '050'), // ISO-4217 numeric for BDT
                    'pg_public_key'        => $e('BHERB_NAGAD_PG_PUBLIC_KEY'),
                    'merchant_private_key' => $e('BHERB_NAGAD_MERCHANT_PRIVATE_KEY'),
                ],
                'live' => [
                    'base'                 => 'https://api.mynagad.com/api/dfs/',
                    'merchant_id'          => $e('BHERB_NAGAD_LIVE_MERCHANT_ID'),
                    'account_number'       => $e('BHERB_NAGAD_LIVE_ACCOUNT_NUMBER'),
                    'currency_code'        => $e('BHERB_NAGAD_LIVE_CURRENCY_CODE', '050'),
                    'pg_public_key'        => $e('BHERB_NAGAD_LIVE_PG_PUBLIC_KEY'),
                    'merchant_private_key' => $e('BHERB_NAGAD_LIVE_MERCHANT_PRIVATE_KEY'),
                ],
            ],
        ],
    ],
];
