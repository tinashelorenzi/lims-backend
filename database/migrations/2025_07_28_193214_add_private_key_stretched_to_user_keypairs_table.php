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
        Schema::table('user_keypairs', function (Blueprint $table) {
            $table->text('private_key_stretched')->nullable()->after('private_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_keypairs', function (Blueprint $table) {
            $table->dropColumn('private_key_stretched');
        });
    }
};