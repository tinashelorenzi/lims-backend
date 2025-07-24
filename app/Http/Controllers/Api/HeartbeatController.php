<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HeartbeatController extends Controller
{
    /**
     * Send heartbeat ping to keep session alive
     */
    public function ping(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get device info from request headers
        $deviceInfo = $this->getDeviceInfo($request);
        
        // Update user's heartbeat
        $user->updateHeartbeat($deviceInfo);
        
        return response()->json([
            'success' => true,
            'message' => 'Heartbeat received',
            'data' => [
                'user_id' => $user->id,
                'session_status' => $user->session_status,
                'last_heartbeat_at' => $user->last_heartbeat_at,
                'server_time' => now(),
                'is_online' => $user->isOnline(),
            ],
        ]);
    }
    
    /**
     * Get current session status
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $user->id,
                'session_status' => $user->session_status,
                'session_status_label' => $user->getSessionStatusLabel(),
                'last_heartbeat_at' => $user->last_heartbeat_at,
                'last_login_at' => $user->last_login_at,
                'device_info' => $user->device_info,
                'is_online' => $user->isOnline(),
                'is_away' => $user->isAway(),
                'should_be_offline' => $user->shouldBeOffline(),
                'server_time' => now(),
            ],
        ]);
    }
    
    /**
     * Get online users (for admins or messaging features)
     */
    public function onlineUsers(Request $request): JsonResponse
    {
        // You might want to add authorization here
        // $this->authorize('viewAny', User::class);
        
        $onlineUsers = \App\Models\User::online()
            ->select(['id', 'first_name', 'last_name', 'user_type', 'last_heartbeat_at', 'device_info'])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'user_type' => $user->user_type,
                    'user_type_label' => $user->getUserTypeLabel(),
                    'last_heartbeat_at' => $user->last_heartbeat_at,
                    'device_info' => $user->device_info,
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => [
                'online_users' => $onlineUsers,
                'total_count' => $onlineUsers->count(),
                'server_time' => now(),
            ],
        ]);
    }
    
    /**
     * Manually set user as offline (called when app closes)
     */
    public function setOffline(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->setOffline();
        
        return response()->json([
            'success' => true,
            'message' => 'Session set to offline',
            'data' => [
                'user_id' => $user->id,
                'session_status' => $user->session_status,
            ],
        ]);
    }
    
    /**
     * Extract device information from request
     */
    private function getDeviceInfo(Request $request): string
    {
        $userAgent = $request->header('User-Agent', 'Unknown');
        $platform = $request->header('X-Platform', 'Desktop');
        $version = $request->header('X-App-Version', '1.0.0');
        
        return json_encode([
            'platform' => $platform,
            'version' => $version,
            'user_agent' => $userAgent,
            'ip_address' => $request->ip(),
            'timestamp' => now(),
        ]);
    }
}