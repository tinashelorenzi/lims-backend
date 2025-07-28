<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class LimsSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'is_encrypted',
        'is_system',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Handle encrypted values
     */
    protected function value(): Attribute
    {
        return Attribute::make(
            get: function (string $value) {
                if ($this->is_encrypted) {
                    return Crypt::decryptString($value);
                }
                
                return match ($this->type) {
                    'integer' => (int) $value,
                    'boolean' => (bool) $value,
                    'json' => json_decode($value, true),
                    default => $value,
                };
            },
            set: function (mixed $value) {
                $processedValue = match ($this->type) {
                    'integer' => (string) $value,
                    'boolean' => $value ? '1' : '0',
                    'json' => json_encode($value),
                    default => (string) $value,
                };

                if ($this->is_encrypted) {
                    return Crypt::encryptString($processedValue);
                }

                return $processedValue;
            }
        );
    }

    /**
     * Get a setting value by key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, mixed $value, string $type = 'string', bool $isEncrypted = false, ?string $description = null): self
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'is_encrypted' => $isEncrypted,
                'description' => $description,
            ]
        );
    }

    /**
     * Get all settings as key-value pairs
     */
    public static function getAllSettings(): array
    {
        return self::all()->pluck('value', 'key')->toArray();
    }

    /**
     * Get lab information settings
     */
    public static function getLabInfo(): array
    {
        return [
            'lab_name' => self::get('lab_name', ''),
            'lab_address' => self::get('lab_address', ''),
            'lab_phone' => self::get('lab_phone', ''),
            'lab_email' => self::get('lab_email', ''),
            'lab_license_number' => self::get('lab_license_number', ''),
        ];
    }

    /**
     * Set lab information
     */
    public static function setLabInfo(array $info): void
    {
        foreach ($info as $key => $value) {
            self::set($key, $value, 'string', false, "Lab {$key}");
        }
    }

    /**
     * Get or generate group keypair
     */
    public static function getGroupKeypair(): array
    {
        $privateKey = self::get('group_private_key');
        $publicKey = self::get('group_public_key');

        if (!$privateKey || !$publicKey) {
            // Generate new group keypair
            $keypair = UserKeypair::generateKeypair(4096); // Stronger key for group
            
            self::set('group_private_key', $keypair['private_key'], 'string', true, 'Group private key for LIMS');
            self::set('group_public_key', $keypair['public_key'], 'string', false, 'Group public key for LIMS');
            
            return $keypair;
        }

        return [
            'private_key' => $privateKey,
            'public_key' => $publicKey,
        ];
    }
}