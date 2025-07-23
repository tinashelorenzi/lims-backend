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
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('method', 10);
            $table->string('endpoint', 500);
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->json('response_headers')->nullable();
            $table->json('response_body')->nullable();
            $table->integer('response_status');
            $table->float('response_time', 8, 2); // Time in milliseconds
            $table->string('session_id')->nullable();
            $table->string('request_id')->nullable();
            $table->string('error_type')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('logged_at');
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['user_id', 'logged_at']);
            $table->index(['method', 'endpoint']);
            $table->index(['ip_address', 'logged_at']);
            $table->index(['response_status', 'logged_at']);
            $table->index('logged_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};