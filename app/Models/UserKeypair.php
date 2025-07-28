<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;
use App\Services\KeyStretchingService;

class UserKeypair extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'public_key',
        'private_key',
        'private_key_stretched',
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
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * The stretched private key should be encrypted when stored
     */
    protected function privateKeyStretched(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
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
     * Generate a new RSA keypair with key stretching
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
     * Create a new keypair for a user with key stretching
     */
    public static function createForUser(User $user, int $bits = 2048): self
    {
        // Deactivate any existing keypairs
        self::where('user_id', $user->id)->update(['is_active' => false]);

        $keypair = self::generateKeypair($bits);
        
        // Apply key stretching to private key
        $stretchedPrivateKey = KeyStretchingService::stretchPrivateKey($keypair['private_key'], (string)$user->id);

        return self::create([
            'user_id' => $user->id,
            'public_key' => $keypair['public_key'],
            'private_key' => $keypair['private_key'], // Store original encrypted
            'private_key_stretched' => $stretchedPrivateKey, // Store stretched version
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

    /**
     * Get the stretched private key for additional security operations
     */
    public function getStretchedPrivateKey(): ?string
    {
        return $this->private_key_stretched;
    }

    /**
     * Verify key stretching integrity
     */
    public function verifyKeyStretching(): bool
    {
        if (!$this->private_key || !$this->private_key_stretched) {
            return false;
        }

        $regeneratedStretch = KeyStretchingService::stretchPrivateKey(
            $this->private_key, 
            (string)$this->user_id
        );

        return hash_equals($this->private_key_stretched, $regeneratedStretch);
    }
}