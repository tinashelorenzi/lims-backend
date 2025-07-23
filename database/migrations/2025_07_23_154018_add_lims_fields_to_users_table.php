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
            $table->string('first_name')->after('name');
            $table->string('last_name')->after('first_name');
            $table->string('phone_number')->nullable()->after('email');
            $table->date('date_hired')->nullable()->after('phone_number');
            $table->timestamp('last_login_at')->nullable()->after('date_hired');
            $table->enum('user_type', [
                'admin',
                'lab_technician', 
                'quality_control',
                'supervisor',
                'researcher',
                'manager'
            ])->default('lab_technician')->after('last_login_at');
            $table->boolean('account_is_set')->default(false)->after('user_type');
            $table->boolean('is_active')->default(true)->after('account_is_set');
            
            // Drop the old name column since we're using first_name and last_name
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->dropColumn([
                'first_name',
                'last_name', 
                'phone_number',
                'date_hired',
                'last_login_at',
                'user_type',
                'account_is_set',
                'is_active'
            ]);
        });
    }
};