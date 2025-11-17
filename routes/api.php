<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Admin only routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Admin dashboard']);
        });
    });

    // Vendor only routes
    Route::middleware('role:vendor')->prefix('vendor')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Vendor dashboard']);
        });
    });

    // User routes (accessible by all authenticated users)
    Route::prefix('user')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'User dashboard']);
        });
    });

    // Routes accessible by multiple roles
    Route::middleware('role:admin,vendor')->prefix('management')->group(function () {
        Route::get('/stats', function () {
            return response()->json(['message' => 'Management stats']);
        });
    });
});
