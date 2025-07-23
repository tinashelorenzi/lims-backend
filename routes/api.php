<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public authentication routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

// Protected routes
Route::middleware(['auth:sanctum', 'api.logging'])->group(function () {
    
    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
        Route::get('profile', [AuthController::class, 'profile']);
        Route::patch('profile', [AuthController::class, 'updateProfile']);
        Route::post('setup-account', [AuthController::class, 'setupAccount']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
        Route::post('update-login', [AuthController::class, 'updateLogin']);
        Route::get('tokens', [AuthController::class, 'activeTokens']);
        Route::delete('tokens/{tokenId}', [AuthController::class, 'revokeToken']);
    });
    
    // Legacy user route for backward compatibility
    Route::get('user', [AuthController::class, 'profile']);
    
    // Health check endpoint
    Route::get('health', function () {
        return response()->json([
            'success' => true,
            'message' => 'API is healthy',
            'timestamp' => now()->toISOString(),
            'user' => auth()->user()->only(['id', 'email', 'first_name', 'last_name']),
        ]);
    });
});