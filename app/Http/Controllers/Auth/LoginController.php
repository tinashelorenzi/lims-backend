<?php

// Add this to your existing login method or create an event listener

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Handle a login request to the application.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            // Update last login time
            Auth::user()->updateLastLogin();
            
            // Check if user needs to setup their account
            if (Auth::user()->needsAccountSetup()) {
                return redirect()->route('account.setup');
            }

            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }
}

// Create an event listener alternative (recommended approach)
// First create the listener:
// php artisan make:listener UpdateLastLogin

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class UpdateLastLogin
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $event->user->updateLastLogin();
    }
}

// Then register it in App\Providers\EventServiceProvider:
/*
protected $listen = [
    'Illuminate\Auth\Events\Login' => [
        'App\Listeners\UpdateLastLogin',
    ],
];
*/