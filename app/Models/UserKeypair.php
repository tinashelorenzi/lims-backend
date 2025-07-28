<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class UserKeypair extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'public_key',
        'private_key',
        'key_algorithm',
        'generated_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * The private key should be encrypted when stored
     */
    protected function privateKey(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => Crypt::decryptString($value),
            set: fn (string $value) => Crypt::encryptString($value),
        );
    }

    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a new RSA keypair
     */
    public static function generateKeypair(int $bits = 2048): array
    {
        $config = [
            "digest_alg" => "sha256",
            "private_key_bits" => $bits,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        // Create the private and public key
        $res = openssl_pkey_new($config);
        
        if (!$res) {
            throw new \Exception('Failed to generate keypair: ' . openssl_error_string());
        }

        // Extract the private key
        openssl_pkey_export($res, $privateKey);

        // Extract the public key
        $publicKeyDetails = openssl_pkey_get_details($res);
        $publicKey = $publicKeyDetails["key"];

        return [
            'private_key' => $privateKey,
            'public_key' => $publicKey,
        ];
    }

    /**
     * Create a new keypair for a user
     */
    public static function createForUser(User $user, int $bits = 2048): self
    {
        // Deactivate any existing keypairs
        self::where('user_id', $user->id)->update(['is_active' => false]);

        $keypair = self::generateKeypair($bits);

        return self::create([
            'user_id' => $user->id,
            'public_key' => $keypair['public_key'],
            'private_key' => $keypair['private_key'],
            'key_algorithm' => "RSA-{$bits}",
            'generated_at' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * Get the active keypair for a user
     */
    public static function getActiveForUser(User $user): ?self
    {
        return self::where('user_id', $user->id)
            ->where('is_active', true)
            ->latest('generated_at')
            ->first();
    }
}