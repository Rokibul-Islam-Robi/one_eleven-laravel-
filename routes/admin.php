<?php

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;

/*
 | Admin CMS (original /admin/{module}/{action}/{id} router).
 | Session key `admin_user`; modules are gated by AdminModuleAccess (role/permission matrix).
 */
Route::prefix('admin')->group(function () {
    Route::match(['get', 'post'], 'login', [AdminController::class, 'login'])->name('admin.login');
    Route::match(['get', 'post'], 'logout', [AdminController::class, 'logout'])->name('admin.logout');

    Route::match(['get', 'post'], '/{module?}/{action?}/{id?}', [AdminController::class, 'handle'])
        ->middleware(['admin.auth', 'admin.module'])
        ->where(['module' => '[A-Za-z0-9_.\-]+', 'action' => '[A-Za-z0-9_\-]+', 'id' => '[0-9]+'])
        ->name('admin.page');
});
