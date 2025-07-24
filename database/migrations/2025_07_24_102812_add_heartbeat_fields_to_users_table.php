<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_heartbeat_at')->nullable()->after('last_login_at');
            $table->enum('session_status', ['online', 'offline', 'away'])->default('offline')->after('last_heartbeat_at');
            $table->string('device_info')->nullable()->after('session_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_heartbeat_at',
                'session_status', 
                'device_info'
            ]);
        });
    }
};