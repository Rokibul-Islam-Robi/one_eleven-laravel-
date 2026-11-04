<?php

return [
    /* Storefront constants from the original includes/bootstrap.php */
    'promo_code'         => 'BHERB20',
    'free_shipping_from' => 5000,

    /* Product catalogue the storefront reads (64 products, unchanged original file).
       The `products` DB table (100 rows) belongs to the admin CMS and is NOT merged. */
    'catalog_file' => database_path('data/products.json'),

    /* Prescription uploads live on the private "local" disk under this folder. */
    'prescription_dir' => 'uploads/prescriptions',

    /* Admin session limits (original admin/includes/config.php) */
    'admin' => [
        'idle'         => 1800,
        'absolute'     => 28800,
        'max_attempts' => 5,
        'lockout'      => 900,
        'per_page'     => 15,
        'currency'     => '৳',
    ],
];
