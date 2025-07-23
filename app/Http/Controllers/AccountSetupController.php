<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AccountSetupController extends Controller
{
    /**
     * Show the account setup form.
     */
    public function show()
    {
        $user = auth()->user();
        
        // Redirect if account is already set up
        if ($user->account_is_set) {
            return redirect()->route('dashboard')
                ->with('info', 'Your account is already set up.');
        }

        return view('auth.account-setup');
    }

    /**
     * Handle the account setup form submission.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        
        // Redirect if account is already set up
        if ($user->account_is_set) {
            return redirect()->route('dashboard')
                ->with('info', 'Your account is already set up.');
        }

        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        // Update password and mark account as set
        $user->update([
            'password' => Hash::make($request->password),
            'account_is_set' => true,
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Your account has been set up successfully!');
    }
}