<?php

namespace App\Features\QRCodeTicketing\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class QRCodeEncryptionService
{
    /**
     * Dedicated encryption key for QR codes.
     * Must be set via QR_ENCRYPTION_KEY env var - do NOT fall back to APP_KEY
     * as that is the master Laravel key used for all encryption across the app.
     * Rotate this key independently when needed.
     */
    private static function getKey(): string
    {
        $key = env('QR_ENCRYPTION_KEY');
        if (!$key) {
            throw new \RuntimeException('QR_ENCRYPTION_KEY not configured. Set it in .env to a dedicated 32-byte base64-encoded value.');
        }
        return base64_decode($key);
    }

    /**
     * Encrypt a payload using AES-256-CBC with the dedicated QR key.
     */
    public static function encrypt(array $payload): string
    {
        $key = self::getKey();
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt(
            json_encode($payload),
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt a payload using AES-256-CBC with the dedicated QR key.
     */
    public static function decrypt(string $encryptedPayload): ?array
    {
        try {
            $key = self::getKey();
            $data = base64_decode($encryptedPayload);
            $iv = substr($data, 0, 16);
            $encrypted = substr($data, 16);
            $decrypted = openssl_decrypt(
                $encrypted,
                'AES-256-CBC',
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );
            if ($decrypted === false) {
                return null;
            }
            return json_decode($decrypted, true);
        } catch (\Throwable $e) {
            Log::warning('QR decryption failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate HMAC signature for QR payload.
     */
    public static function sign(array $payload): string
    {
        $key = self::getKey();
        return hash_hmac('sha256', json_encode($payload), $key);
    }

    /**
     * Verify HMAC signature for QR payload.
     */
    public static function verifySignature(array $payload, string $signature): bool
    {
        $expected = self::sign($payload);
        return hash_equals($expected, $signature);
    }
}
