<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateUserSessionStatus extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'users:update-session-status';

    /**
     * The console command description.
     */
    protected $description = 'Update user session status based on heartbeat timestamps';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating user session statuses...');
        
        // Set users as offline if no heartbeat for 10+ minutes
        $offlineCount = User::where('session_status', '!=', 'offline')
            ->where(function ($query) {
                $query->where('last_heartbeat_at', '<', now()->subMinutes(10))
                      ->orWhereNull('last_heartbeat_at');
            })
            ->update(['session_status' => 'offline']);
            
        // Set users as away if heartbeat between 2-10 minutes ago
        $awayCount = User::where('session_status', '!=', 'away')
            ->where('last_heartbeat_at', '>=', now()->subMinutes(10))
            ->where('last_heartbeat_at', '<', now()->subMinutes(2))
            ->update(['session_status' => 'away']);
            
        // Set users as online if heartbeat within last 2 minutes
        $onlineCount = User::where('session_status', '!=', 'online')
            ->where('last_heartbeat_at', '>=', now()->subMinutes(2))
            ->update(['session_status' => 'online']);
        
        $this->info("Session status updated:");
        $this->info("- Set {$offlineCount} users as offline");
        $this->info("- Set {$awayCount} users as away");
        $this->info("- Set {$onlineCount} users as online");
        
        // Show current status summary
        $statusCounts = User::select('session_status', DB::raw('count(*) as count'))
            ->groupBy('session_status')
            ->pluck('count', 'session_status')
            ->toArray();
            
        $this->info("\nCurrent session status summary:");
        foreach ($statusCounts as $status => $count) {
            $this->info("- {$status}: {$count} users");
        }
        
        return Command::SUCCESS;
    }
}