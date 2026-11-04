<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Payment gateways (SSLCommerz / bKash / Nagad) call back from their own servers,
        // so those endpoints cannot carry a CSRF token. Each handler verifies the callback itself.
        $middleware->validateCsrfTokens(except: [
            'payment/success', 'payment/success.php',
            'payment/fail', 'payment/fail.php',
            'payment/cancel', 'payment/cancel.php',
            'payment/ipn', 'payment/ipn.php',
        ]);
        // The admin CMS compares raw form values (as the original $_POST did): no trimming / null conversion.
        \Illuminate\Foundation\Http\Middleware\TrimStrings::skipWhen(fn ($request) => $request->is('admin', 'admin/*'));
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::skipWhen(fn ($request) => $request->is('admin', 'admin/*'));
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuthenticate::class,
            'admin.module' => \App\Http\Middleware\AdminModuleAccess::class,
            'seller.auth' => \App\Http\Middleware\SellerAuthenticate::class,
            'seller.active' => \App\Http\Middleware\ActiveSeller::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
