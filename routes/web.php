<?php

/*
| One route file per area, matching the original PHP project layout:
|   storefront.php  front controller pages + api/cart|search|auth|quote
|   checkout.php    checkout, api/checkout, order-success, payment/* callbacks
|   admin.php       /admin panel (login, dashboard and the 12 modules)
*/
require __DIR__.'/storefront.php';
require __DIR__.'/checkout.php';
require __DIR__.'/admin.php';
require __DIR__.'/seller.php';
