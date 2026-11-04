<?php

use App\Http\Controllers\Storefront\ApiController;
use App\Http\Controllers\Storefront\CustomerAccountController;
use App\Http\Controllers\Storefront\PageController;
use Illuminate\Support\Facades\Route;

// Pages: same URLs as the original index.php router (GET renders, POST = classic form action).
Route::match(['get', 'post'], '/', [PageController::class, 'show'])->defaults('view', 'home');
Route::match(['get', 'post'], '/index.php', [PageController::class, 'show'])->defaults('view', 'home');
Route::match(['get', 'post'], '/product/{id}', [PageController::class, 'show'])->defaults('view', 'product');
Route::match(['get', 'post'], '/brand/{slug}', [PageController::class, 'show'])->defaults('view', 'brand');
Route::match(['get', 'post'], '/brand.php', [PageController::class, 'show'])->defaults('view', 'brand');
Route::match(['get', 'post'], '/brands', [PageController::class, 'show'])->defaults('view', 'brands');
Route::match(['get', 'post'], '/category/{slug}', [PageController::class, 'show'])->defaults('view', 'category');
Route::match(['get', 'post'], '/category.php', [PageController::class, 'show'])->defaults('view', 'category');
foreach (['deals', 'bestsellers', 'new'] as $v) {
    Route::match(['get', 'post'], '/' . $v, [PageController::class, 'show'])->defaults('view', 'listing');
}
foreach (['search', 'cart', 'wishlist'] as $v) {
    Route::match(['get', 'post'], '/' . $v, [PageController::class, 'show'])->defaults('view', $v);
}
Route::match(['get', 'post'], '/account', [PageController::class, 'show'])->defaults('view', 'account');
Route::get('/account/avatar', [CustomerAccountController::class, 'avatar']);
Route::get('/account/prescriptions/{id}/file', [CustomerAccountController::class, 'prescriptionFile'])->whereNumber('id');
Route::post('/account/prescriptions/{id}/delete', [CustomerAccountController::class, 'deletePrescription'])->whereNumber('id');
Route::post('/account/profile/avatar', [CustomerAccountController::class, 'uploadAvatar']);
Route::post('/account/logout', [CustomerAccountController::class, 'logout']);
foreach (['products', 'prescriptions', 'addresses', 'reviews', 'profile', 'patients'] as $section) {
    Route::get('/account/' . $section, [CustomerAccountController::class, 'show'])->defaults('section', $section);
    Route::post('/account/' . $section, [CustomerAccountController::class, 'save'])->defaults('section', $section);
}

// JSON API (exact paths the original JS calls)
Route::get('/api/cart.php', [ApiController::class, 'cart']);
Route::post('/api/cart.php', [ApiController::class, 'cart']);
Route::get('/api/search.php', [ApiController::class, 'search']);
Route::match(['get', 'post'], '/api/auth.php', [ApiController::class, 'auth']);

// Anything else: original 404 page
Route::fallback(function () {
    return PageController::render('not-found', [], 404);
});
