<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserKeypair;
use App\Models\LimsSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KeypairController extends Controller
{
    /**
     * Generate and store user keypair
     */
    public function generateUserKeypair(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            DB::beginTransaction();

            // Generate new keypair for the user
            $userKeypair = $user->generateKeypair();

            // Get or generate group keypair
            $groupKeypair = LimsSetting::getGroupKeypair();

            DB::commit();

            Log::info('User keypair generated successfully', [
                'user_id' => $user->id,
                'keypair_id' => $userKeypair->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Keypair generated successfully',
                'data' => [
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
            
            Log::error('Failed to generate user keypair', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate keypair',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's active keypair
     */
    public function getUserKeypair(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $keypair = $user->activeKeypair;

            if (!$keypair) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active keypair found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $keypair->id,
                    'public_key' => $keypair->public_key,
                    'private_key' => $keypair->private_key,
                    'algorithm' => $keypair->key_algorithm,
                    'generated_at' => $keypair->generated_at->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user keypair', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve keypair',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get group public key only
     */
    public function getGroupPublicKey(Request $request): JsonResponse
    {
        try {
            $groupKeypair = LimsSetting::getGroupKeypair();

            return response()->json([
                'success' => true,
                'data' => [
                    'public_key' => $groupKeypair['public_key'],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get group public key', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve group public key',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Regenerate user keypair
     */
    public function regenerateUserKeypair(Request $request): JsonResponse
    {
        $request->validate([
            'bits' => 'sometimes|integer|in:2048,3072,4096',
        ]);

        try {
            $user = auth()->user();
            $bits = $request->input('bits', 2048);

            DB::beginTransaction();

            // Generate new keypair
            $userKeypair = $user->generateKeypair($bits);

            DB::commit();

            Log::info('User keypair regenerated successfully', [
                'user_id' => $user->id,
                'keypair_id' => $userKeypair->id,
                'bits' => $bits,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Keypair regenerated successfully',
                'data' => [
                    'id' => $userKeypair->id,
                    'public_key' => $userKeypair->public_key,
                    'private_key' => $userKeypair->private_key,
                    'algorithm' => $userKeypair->key_algorithm,
                    'generated_at' => $userKeypair->generated_at->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to regenerate user keypair', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate keypair',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}