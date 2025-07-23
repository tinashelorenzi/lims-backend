<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountSetupController extends Controller
{
    /**
     * Show the account setup form
     */
    public function show()
    {
        $user = auth()->user();
        
        // If user has already set up their account, redirect to dashboard
        if ($user->account_is_set) {
            return redirect()->route('dashboard');
        }
        
        return view('account.setup', compact('user'));
    }

    /**
     * Store the account setup data
     */
    public function store(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();
        
        $user->update([
            'password' => Hash::make($request->password),
            'account_is_set' => true,
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Account setup completed successfully!');
    }
}
