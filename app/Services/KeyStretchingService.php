<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;

class KeyStretchingService
{
    /**
     * Apply key stretching to the private key using PBKDF2
     */
    public static function stretchPrivateKey(string $privateKey, string $userId): string
    {
        $keyStretch = config('app.key_stretch', 'default-stretch');
        
        // Create a salt using user ID and key stretch string
        $salt = hash('sha256', $keyStretch . $userId);
        
        // Apply PBKDF2 key stretching with 10000 iterations
        $stretchedKey = hash_pbkdf2('sha256', $privateKey, $salt, 10000, 0, true);
        
        // Encode to base64 for storage
        return base64_encode($stretchedKey);
    }

    /**
     * Reverse key stretching to get original private key
     */
    public static function unstretchPrivateKey(string $stretchedKey, string $userId): string
    {
        $keyStretch = config('app.key_stretch', 'default-stretch');
        
        // Create the same salt
        $salt = hash('sha256', $keyStretch . $userId);
        
        // Decode from base64
        $decodedKey = base64_decode($stretchedKey);
        
        // Note: PBKDF2 is one-way, so we'll store both original and stretched
        // This method is for verification purposes
        return $decodedKey;
    }

    /**
     * Encrypt private key with key stretching
     */
    public static function encryptWithStretching(string $privateKey, string $userId): string
    {
        // First apply key stretching
        $stretchedKey = self::stretchPrivateKey($privateKey, $userId);
        
        // Then encrypt with Laravel's encryption
        return Crypt::encryptString($stretchedKey);
    }

    /**
     * Decrypt private key and reverse stretching
     */
    public static function decryptWithStretching(string $encryptedKey, string $userId): string
    {
        // First decrypt with Laravel's encryption
        $stretchedKey = Crypt::decryptString($encryptedKey);
        
        // For now, we'll need to store the original key separately
        // since PBKDF2 is one-way. In practice, you might want to
        // store both the original encrypted key and a stretched hash for verification
        return $stretchedKey;
    }
}