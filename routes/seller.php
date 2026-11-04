<?php

use App\Http\Controllers\Seller\SellerAuthController;
use App\Http\Controllers\Seller\SellerOrderController;
use App\Http\Controllers\Seller\SellerPortalController;
use App\Http\Controllers\Seller\SellerProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('seller')->group(function () {
    Route::get('/register', [SellerAuthController::class, 'registerForm']);
    Route::post('/register', [SellerAuthController::class, 'register'])->middleware('throttle:3,1');
    Route::get('/login', [SellerAuthController::class, 'loginForm']);
    Route::post('/login', [SellerAuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/password/forgot', [SellerAuthController::class, 'forgotPasswordForm']);
    Route::post('/password/email', [SellerAuthController::class, 'sendPasswordReset'])->middleware('throttle:3,1');
    Route::get('/password/reset/{token}', [SellerAuthController::class, 'resetPasswordForm'])->name('password.reset');
    Route::post('/password/reset', [SellerAuthController::class, 'resetPassword'])->middleware('throttle:5,1');

    Route::middleware('seller.auth')->group(function () {
        Route::post('/logout', [SellerAuthController::class, 'logout']);
        Route::get('/email/verify', [SellerAuthController::class, 'verificationNotice'])->name('verification.notice');
        Route::post('/email/verification-notification', [SellerAuthController::class, 'sendVerification'])
            ->middleware('throttle:6,1')
            ->name('verification.send');
        Route::get('/email/verify/{id}/{hash}', [SellerAuthController::class, 'verifyEmail'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');

        Route::get('/dashboard', [SellerPortalController::class, 'dashboard']);
        Route::get('/profile', [SellerPortalController::class, 'profile']);
        Route::post('/profile', [SellerPortalController::class, 'updateProfile']);
        Route::post('/profile/payout', [SellerPortalController::class, 'updatePayout'])
            ->middleware('seller.active');
        Route::post('/profile/documents', [SellerPortalController::class, 'uploadDocument']);
        Route::get('/documents/{id}', [SellerPortalController::class, 'document'])->whereNumber('id');

        Route::middleware('seller.active')->group(function () {
            Route::get('/orders', [SellerOrderController::class, 'index'])->name('seller.orders.index');
            Route::get('/orders/{id}', [SellerOrderController::class, 'show'])->whereNumber('id')->name('seller.orders.show');
            Route::post('/orders/{id}/status', [SellerOrderController::class, 'update'])->whereNumber('id')->name('seller.orders.update');
            Route::get('/products', [SellerProductController::class, 'index']);
            Route::get('/products/create', [SellerProductController::class, 'create']);
            Route::post('/products', [SellerProductController::class, 'store']);
            Route::get('/products/{id}/edit', [SellerProductController::class, 'edit'])->whereNumber('id');
            Route::post('/products/{id}', [SellerProductController::class, 'update'])->whereNumber('id');
            Route::get('/inventory', [SellerProductController::class, 'inventory']);
            Route::post('/products/{id}/inventory', [SellerProductController::class, 'adjustInventory'])->whereNumber('id');
        });
    });
});
