<?php

/* Admin CMS configuration (original admin/includes/config.php). */
return [
    'name'          => 'RBS Pharmaceutical',
    'sub'           => 'Admin CMS · bHerb Storefront',
    'currency'      => '৳',
    'per_page'      => 15,
    'max_upload_mb' => 5,
    'session' => [
        'idle'         => 1800,    // 30 min inactivity
        'absolute'     => 28800,   // 8 hours max session
        'max_attempts' => 5,       // failed logins per window
        'lockout'      => 900,     // 15 min lockout window
    ],
];
