<?php

// Ensure /tmp storage paths exist for Vercel serverless environment
if (isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL'])) {
    $storagePaths = [
        '/tmp/views',
        '/tmp/storage/framework/cache/data',
        '/tmp/storage/framework/sessions',
        '/tmp/storage/framework/views',
        '/tmp/storage/logs',
    ];
    foreach ($storagePaths as $path) {
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }
    }
}

// Forward all requests to Laravel's public/index.php
require __DIR__ . '/../public/index.php';
