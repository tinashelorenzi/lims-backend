<?php

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Add these routes to your existing routes/web.php file

Route::middleware(['auth'])->group(function () {
    
    // Admin routes - only accessible by admin users
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        
        // User management routes
        Route::resource('users', UserController::class);
        
        // Additional user management actions
        Route::patch('users/{user}/reset-password', [UserController::class, 'resetPassword'])
            ->name('users.reset-password');
        
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
            ->name('users.toggle-status');
    });
});

// API routes for desktop app integration
Route::middleware(['auth:sanctum'])->prefix('api')->group(function () {
    
    // User profile endpoints
    Route::get('user/profile', function () {
        return response()->json(auth()->user());
    });
    
    Route::patch('user/setup-account', function (Request $request) {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        $user = auth()->user();
        $user->update([
            'password' => Hash::make($request->password),
            'account_is_set' => true,
        ]);
        
        return response()->json(['message' => 'Account setup completed successfully']);
    });
    
    Route::post('user/update-login', function () {
        auth()->user()->updateLastLogin();
        return response()->json(['message' => 'Login time updated']);
    });
});