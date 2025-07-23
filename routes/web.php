<?php

use App\Http\Controllers\AccountSetupController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

// Add these routes to your existing routes/web.php file

Route::middleware(['auth'])->group(function () {

    // Account setup routes
    Route::get('account/setup', [AccountSetupController::class, 'show'])
        ->name('account.setup');
    
    Route::post('account/setup', [AccountSetupController::class, 'store'])
        ->name('account.setup.store');
    
    // Protected routes that require account setup
    Route::middleware(['account.setup'])->group(function () {
        
        // Dashboard and other main application routes
        Route::get('/dashboard', function () {
            return view('dashboard');
        })->name('dashboard');
        
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