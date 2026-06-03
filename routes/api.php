<?php

use App\Http\Controllers\Mobile\MobileAuthController;
use App\Http\Controllers\Mobile\MobileSyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function () {
    Route::post('/login', [MobileAuthController::class, 'login']);

    Route::middleware('mobile.auth')->group(function () {
        Route::post('/logout', [MobileAuthController::class, 'logout']);
        Route::get('/bootstrap', [MobileSyncController::class, 'bootstrap']);
        Route::post('/sync', [MobileSyncController::class, 'sync']);
    });
});
