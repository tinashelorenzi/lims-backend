<?php

namespace Database\Seeders;

use App\Models\LimsSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LimsSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultSettings = [
            // Lab Information
            [
                'key' => 'lab_name',
                'value' => 'LIMS Laboratory',
                'type' => 'string',
                'description' => 'Laboratory name displayed throughout the system',
                'is_encrypted' => false,
                'is_system' => false,
            ],
            [
                'key' => 'lab_address',
                'value' => '',
                'type' => 'string',
                'description' => 'Laboratory physical address',
                'is_encrypted' => false,
                'is_system' => false,
            ],
            [
                'key' => 'lab_phone',
                'value' => '',
                'type' => 'string',
                'description' => 'Laboratory contact phone number',
                'is_encrypted' => false,
                'is_system' => false,
            ],
            [
                'key' => 'lab_email',
                'value' => '',
                'type' => 'string',
                'description' => 'Laboratory contact email address',
                'is_encrypted' => false,
                'is_system' => false,
            ],
            [
                'key' => 'lab_license_number',
                'value' => '',
                'type' => 'string',
                'description' => 'Laboratory license or certification number',
                'is_encrypted' => false,
                'is_system' => false,
            ],

            // System Configuration
            [
                'key' => 'system_timezone',
                'value' => 'UTC',
                'type' => 'string',
                'description' => 'Default system timezone',
                'is_encrypted' => false,
                'is_system' => true,
            ],
            [
                'key' => 'session_timeout',
                'value' => '480', // 8 hours in minutes
                'type' => 'integer',
                'description' => 'User session timeout in minutes',
                'is_encrypted' => false,
                'is_system' => true,
            ],
            [
                'key' => 'max_file_upload_size',
                'value' => '10485760', // 10MB in bytes
                'type' => 'integer',
                'description' => 'Maximum file upload size in bytes',
                'is_encrypted' => false,
                'is_system' => true,
            ],
            [
                'key' => 'enable_audit_logging',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Enable comprehensive audit logging',
                'is_encrypted' => false,
                'is_system' => true,
            ],
            [
                'key' => 'enable_two_factor_auth',
                'value' => '0',
                'type' => 'boolean',
                'description' => 'Enable two-factor authentication requirement',
                'is_encrypted' => false,
                'is_system' => true,
            ],

            // Security Settings
            [
                'key' => 'password_min_length',
                'value' => '8',
                'type' => 'integer',
                'description' => 'Minimum password length requirement',
                'is_encrypted' => false,
                'is_system' => true,
            ],
            [
                'key' => 'password_require_special_chars',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Require special characters in passwords',
                'is_encrypted' => false,
                'is_system' => true,
            ],
            [
                'key' => 'failed_login_attempts_limit',
                'value' => '5',
                'type' => 'integer',
                'description' => 'Maximum failed login attempts before account lockout',
                'is_encrypted' => false,
                'is_system' => true,
            ],
            [
                'key' => 'account_lockout_duration',
                'value' => '30', // minutes
                'type' => 'integer',
                'description' => 'Account lockout duration in minutes',
                'is_encrypted' => false,
                'is_system' => true,
            ],

            // API Configuration
            [
                'key' => 'api_rate_limit_per_minute',
                'value' => '100',
                'type' => 'integer',
                'description' => 'API requests per minute limit per user',
                'is_encrypted' => false,
                'is_system' => true,
            ],
            [
                'key' => 'api_token_lifetime_days',
                'value' => '30',
                'type' => 'integer',
                'description' => 'API token lifetime in days',
                'is_encrypted' => false,
                'is_system' => true,
            ],

            // Data Retention
            [
                'key' => 'audit_log_retention_days',
                'value' => '365',
                'type' => 'integer',
                'description' => 'Audit log retention period in days',
                'is_encrypted' => false,
                'is_system' => true,
            ],
            [
                'key' => 'api_log_retention_days',
                'value' => '90',
                'type' => 'integer',
                'description' => 'API log retention period in days',
                'is_encrypted' => false,
                'is_system' => true,
            ],

            // Notification Settings
            [
                'key' => 'email_notifications_enabled',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Enable email notifications',
                'is_encrypted' => false,
                'is_system' => false,
            ],
            [
                'key' => 'smtp_host',
                'value' => '',
                'type' => 'string',
                'description' => 'SMTP server hostname',
                'is_encrypted' => false,
                'is_system' => false,
            ],
            [
                'key' => 'smtp_port',
                'value' => '587',
                'type' => 'integer',
                'description' => 'SMTP server port',
                'is_encrypted' => false,
                'is_system' => false,
            ],
            [
                'key' => 'smtp_username',
                'value' => '',
                'type' => 'string',
                'description' => 'SMTP authentication username',
                'is_encrypted' => true,
                'is_system' => false,
            ],
            [
                'key' => 'smtp_password',
                'value' => '',
                'type' => 'string',
                'description' => 'SMTP authentication password',
                'is_encrypted' => true,
                'is_system' => false,
            ],

            // Backup Settings
            [
                'key' => 'auto_backup_enabled',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Enable automatic database backups',
                'is_encrypted' => false,
                'is_system' => true,
            ],
            [
                'key' => 'backup_frequency_hours',
                'value' => '24',
                'type' => 'integer',
                'description' => 'Backup frequency in hours',
                'is_encrypted' => false,
                'is_system' => true,
            ],
            [
                'key' => 'backup_retention_days',
                'value' => '30',
                'type' => 'integer',
                'description' => 'Backup retention period in days',
                'is_encrypted' => false,
                'is_system' => true,
            ],
        ];

        foreach ($defaultSettings as $setting) {
            LimsSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        // Generate initial group keypair
        try {
            LimsSetting::getGroupKeypair();
            $this->command->info('✓ Group keypair generated successfully');
        } catch (\Exception $e) {
            $this->command->error('✗ Failed to generate group keypair: ' . $e->getMessage());
        }
    }
}