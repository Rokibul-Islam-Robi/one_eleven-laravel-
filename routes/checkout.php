<?php

use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\OrderController;
use Illuminate\Support\Facades\Route;

// Pages (GET renders; POST = classic form action, as in the original actions.php)
Route::match(['get', 'post'], '/checkout', [CheckoutController::class, 'show']);
Route::match(['get', 'post'], '/order-success', [OrderController::class, 'show']);

// JSON endpoints (exact paths the original JS calls)
Route::match(['get', 'post'], '/api/checkout.php', [CheckoutController::class, 'apiCheckout']);
Route::get('/api/quote.php', [CheckoutController::class, 'apiQuote']);

// Gateway callbacks and the offline demo gateway (with and without .php)
foreach (['success', 'fail', 'cancel', 'ipn'] as $cb) {
    Route::match(['get', 'post'], "/payment/{$cb}", [PaymentController::class, $cb]);
    Route::match(['get', 'post'], "/payment/{$cb}.php", [PaymentController::class, $cb]);
}
Route::get('/payment/demo', [PaymentController::class, 'demo']);
Route::get('/payment/demo.php', [PaymentController::class, 'demo']);
