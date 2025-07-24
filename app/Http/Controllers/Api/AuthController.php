<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login user and generate token
     */
    public function login(\App\Http\Requests\Api\LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is deactivated',
            ], 403);
        }

        // Update last login
        $user->updateLastLogin();

        // Create token
        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name ?? '',
                    'last_name' => $user->last_name ?? '',
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'user_type' => $user->user_type,
                    'user_type_label' => $user->getUserTypeLabel(),
                    'account_is_set' => $user->account_is_set,
                    'is_active' => $user->is_active,
                    'last_login_at' => $user->last_login_at,
                    'date_hired' => $user->date_hired,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Logout user and revoke token
     */
    public function logout(Request $request)
    {
        // Revoke the current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful',
        ]);
    }

    /**
     * Logout from all devices
     */
    public function logoutAll(Request $request)
    {
        // Revoke all tokens for the user
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices',
        ]);
    }

    /**
     * Get authenticated user profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name ?? '',
                    'last_name' => $user->last_name ?? '',
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'user_type' => $user->user_type,
                    'user_type_label' => $user->getUserTypeLabel(),
                    'account_is_set' => $user->account_is_set,
                    'is_active' => $user->is_active,
                    'last_login_at' => $user->last_login_at,
                    'date_hired' => $user->date_hired,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ],
        ]);
    }

    /**
     * Setup user account (set password for first time)
     */
    public function setupAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Update password and mark account as set
        $user->update([
            'password' => Hash::make($request->password),
            'account_is_set' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account setup completed successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name ?? '',
                    'last_name' => $user->last_name ?? '',
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'user_type' => $user->user_type,
                    'user_type_label' => $user->getUserTypeLabel(),
                    'account_is_set' => $user->account_is_set,
                    'is_active' => $user->is_active,
                    'last_login_at' => $user->last_login_at,
                    'date_hired' => $user->date_hired,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ],
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 400);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'phone_number' => 'sometimes|nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $user->update($request->only(['first_name', 'last_name', 'phone_number']));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name ?? '',
                    'last_name' => $user->last_name ?? '',
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'user_type' => $user->user_type,
                    'user_type_label' => $user->getUserTypeLabel(),
                    'account_is_set' => $user->account_is_set,
                    'is_active' => $user->is_active,
                    'last_login_at' => $user->last_login_at,
                    'date_hired' => $user->date_hired,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ],
        ]);
    }

    /**
     * Update last login timestamp
     */
    public function updateLogin(Request $request)
    {
        $request->user()->updateLastLogin();

        return response()->json([
            'success' => true,
            'message' => 'Login time updated',
        ]);
    }

    /**
     * Get user's active tokens
     */
    public function activeTokens(Request $request)
    {
        $tokens = $request->user()->tokens()->get()->map(function ($token) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
                'is_current' => $token->id === request()->user()->currentAccessToken()->id,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'tokens' => $tokens,
            ],
        ]);
    }

    /**
     * Revoke specific token
     */
    public function revokeToken(Request $request, $tokenId)
    {
        $token = $request->user()->tokens()->find($tokenId);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token not found',
            ], 404);
        }

        $token->delete();

        return response()->json([
            'success' => true,
            'message' => 'Token revoked successfully',
        ]);
    }
}