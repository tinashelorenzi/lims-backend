<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LimsSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class LimsSettingsController extends Controller
{
    /**
     * Get all LIMS settings
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $settings = LimsSetting::getAllSettings();

            return response()->json([
                'success' => true,
                'data' => $settings,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve LIMS settings', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get lab information settings
     */
    public function getLabInfo(Request $request): JsonResponse
    {
        try {
            $labInfo = LimsSetting::getLabInfo();

            return response()->json([
                'success' => true,
                'data' => $labInfo,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve lab info', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve lab information',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update lab information
     */
    public function updateLabInfo(Request $request): JsonResponse
    {
        $request->validate([
            'lab_name' => 'required|string|max:255',
            'lab_address' => 'nullable|string|max:1000',
            'lab_phone' => 'nullable|string|max:50',
            'lab_email' => 'nullable|email|max:255',
            'lab_license_number' => 'nullable|string|max:100',
        ]);

        try {
            $labInfo = $request->only([
                'lab_name',
                'lab_address',
                'lab_phone',
                'lab_email',
                'lab_license_number',
            ]);

            LimsSetting::setLabInfo($labInfo);

            Log::info('Lab information updated', [
                'user_id' => auth()->id(),
                'updated_fields' => array_keys($labInfo),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lab information updated successfully',
                'data' => $labInfo,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update lab info', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update lab information',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific setting
     */
    public function getSetting(Request $request, string $key): JsonResponse
    {
        try {
            $setting = LimsSetting::where('key', $key)->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'key' => $setting->key,
                    'value' => $setting->value,
                    'type' => $setting->type,
                    'description' => $setting->description,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve setting', [
                'user_id' => auth()->id(),
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve setting',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a specific setting
     */
    public function updateSetting(Request $request, string $key): JsonResponse
    {
        $request->validate([
            'value' => 'required',
            'type' => ['sometimes', Rule::in(['string', 'integer', 'boolean', 'json'])],
            'description' => 'nullable|string|max:1000',
            'is_encrypted' => 'sometimes|boolean',
        ]);

        try {
            // Check if it's a system setting and prevent certain modifications
            $existingSetting = LimsSetting::where('key', $key)->first();
            if ($existingSetting && $existingSetting->is_system) {
                // Allow value updates but not deletion of system settings
                if ($request->has('is_system')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot modify system setting properties',
                    ], 403);
                }
            }

            $setting = LimsSetting::set(
                $key,
                $request->input('value'),
                $request->input('type', 'string'),
                $request->boolean('is_encrypted', false),
                $request->input('description')
            );

            Log::info('Setting updated', [
                'user_id' => auth()->id(),
                'key' => $key,
                'type' => $setting->type,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Setting updated successfully',
                'data' => [
                    'key' => $setting->key,
                    'value' => $setting->value,
                    'type' => $setting->type,
                    'description' => $setting->description,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update setting', [
                'user_id' => auth()->id(),
                'key' => $key,
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update setting',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get group keypair (admin only)
     */
    public function getGroupKeypair(Request $request): JsonResponse
    {
        try {
            // Check if user is admin
            if (auth()->user()->user_type !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.',
                ], 403);
            }

            $groupKeypair = LimsSetting::getGroupKeypair();

            return response()->json([
                'success' => true,
                'data' => $groupKeypair,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve group keypair', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve group keypair',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Regenerate group keypair (admin only)
     */
    public function regenerateGroupKeypair(Request $request): JsonResponse
    {
        try {
            // Check if user is admin
            if (auth()->user()->user_type !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.',
                ], 403);
            }

            // Force regeneration by deleting existing keys
            LimsSetting::where('key', 'group_private_key')->delete();
            LimsSetting::where('key', 'group_public_key')->delete();

            // Generate new keypair
            $groupKeypair = LimsSetting::getGroupKeypair();

            Log::warning('Group keypair regenerated', [
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Group keypair regenerated successfully',
                'data' => $groupKeypair,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to regenerate group keypair', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate group keypair',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}