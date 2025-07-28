<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LimsSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        try {
            DB::beginTransaction();

            // Update password and mark account as set
            $user->update([
                'password' => Hash::make($request->password),
                'account_is_set' => true,
            ]);

            // Generate keypair for the user
            $userKeypair = $user->generateKeypair();

            // Ensure group keypair exists
            LimsSetting::getGroupKeypair();

            DB::commit();

            Log::info('Account setup completed with keypair generation', [
                'user_id' => $user->id,
                'keypair_id' => $userKeypair->id,
            ]);

            return redirect()->route('dashboard')
                ->with('success', 'Your account has been set up successfully with security keys generated!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Account setup failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'setup_error' => 'Failed to complete account setup. Please try again.',
            ]);
        }
    }

    /**
     * API version of account setup for Flutter app
     */
    public function apiStore(Request $request)
    {
        $user = auth()->user();
        
        // Check if account is already set up
        if ($user->account_is_set) {
            return response()->json([
                'success' => false,
                'message' => 'Account is already set up',
            ], 409);
        }

        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The current password is incorrect',
                'errors' => [
                    'current_password' => ['The current password is incorrect.']
                ]
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Update password and mark account as set
            $user->update([
                'password' => Hash::make($request->password),
                'account_is_set' => true,
            ]);

            // Generate keypair for the user
            $userKeypair = $user->generateKeypair();

            // Get or generate group keypair
            $groupKeypair = LimsSetting::getGroupKeypair();

            DB::commit();

            Log::info('API account setup completed with keypair generation', [
                'user_id' => $user->id,
                'keypair_id' => $userKeypair->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Account setup completed successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'user_type' => $user->user_type,
                        'account_is_set' => true,
                    ],
                    'user_keypair' => [
                        'id' => $userKeypair->id,
                        'public_key' => $userKeypair->public_key,
                        'private_key' => $userKeypair->private_key,
                        'algorithm' => $userKeypair->key_algorithm,
                        'generated_at' => $userKeypair->generated_at->toISOString(),
                    ],
                    'group_keypair' => [
                        'public_key' => $groupKeypair['public_key'],
                        'private_key' => $groupKeypair['private_key'],
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('API account setup failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete account setup',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}