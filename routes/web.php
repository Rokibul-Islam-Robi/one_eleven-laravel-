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

// Dedicated route to serve public assets seamlessly on serverless platforms
Route::get('assets/{path}', function ($path) {
    $fullPath = public_path('assets/' . $path);
    if (!file_exists($fullPath) || is_dir($fullPath)) {
        abort(404);
    }
    $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    $mimes = [
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
    ];
    $contentType = $mimes[$extension] ?? mime_content_type($fullPath) ?? 'application/octet-stream';
    return response()->file($fullPath, [
        'Content-Type' => $contentType,
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('path', '.*');
