<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\InventoryController;

// Public routes with strict rate limiting
Route::prefix('v1')->middleware(['throttle:auth'])->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('logout', [AuthController::class, 'logout']);
});

// Protected routes with standard rate limiting
Route::prefix('v1')->middleware(['jwt.auth', 'throttle:api'])->group(function () {
    // Auth
    Route::get('me', [AuthController::class, 'me']);

    // Products
    Route::apiResource('products', ProductController::class);

    // Orders
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::post('orders', [OrderController::class, 'store'])->middleware('throttle:orders');
    Route::post('orders/{id}/confirm', [OrderController::class, 'confirm'])->middleware('role:admin,vendor');
    Route::post('orders/{id}/cancel', [OrderController::class, 'cancel']);
    Route::patch('orders/{id}/status', [OrderController::class, 'updateStatus'])->middleware('role:admin,vendor');

    // Inventory
    Route::post('inventory/add', [InventoryController::class, 'addStock'])->middleware('role:admin,vendor');
    Route::post('inventory/deduct', [InventoryController::class, 'deductStock'])->middleware('role:admin,vendor');
});
