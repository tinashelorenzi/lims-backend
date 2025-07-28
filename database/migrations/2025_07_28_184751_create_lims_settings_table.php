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
        Schema::create('lims_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Setting key (e.g., 'lab_name', 'group_private_key')
            $table->longText('value')->nullable(); // Setting value (encrypted for sensitive data)
            $table->string('type')->default('string'); // string, integer, boolean, json, encrypted
            $table->text('description')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->boolean('is_system')->default(false); // System settings can't be deleted
            $table->timestamps();
            
            // Indexes
            $table->index('key');
            $table->index(['is_system', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lims_settings');
    }
};