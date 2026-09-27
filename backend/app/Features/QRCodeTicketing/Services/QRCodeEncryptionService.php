<?php

namespace App\Features\QRCodeTicketing\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class QRCodeEncryptionService
{
    /**
     * Dedicated encryption key for QR codes.
     *
     * Production requirement:
     * - QR_ENCRYPTION_KEY MUST be set to a dedicated base64-encoded 32-byte key.
     * - Do NOT fall back to APP_KEY; APP_KEY is the master Laravel encryption key.
     *
     * Rotation procedure (zero-downtime, invalidates existing QR codes gradually):
     * 1. Generate a new key: openssl rand -base64 32
     * 2. Set QR_ENCRYPTION_KEY_NEW=<new_key> and QR_ENCRYPTION_KEY_ROTATION_AT=<future ISO-8601 timestamp>
     * 3. Deploy. During the rotation window, decryption tries the current key first,
     *    then the new key, so old QRs keep working while newly issued QRs use the new key.
     * 4. After QR_ENCRYPTION_KEY_ROTATION_AT passes, promote QR_ENCRYPTION_KEY_NEW to QR_ENCRYPTION_KEY
     *    and unset QR_ENCRYPTION_KEY_NEW / QR_ENCRYPTION_KEY_ROTATION_AT.
     * 5. Any QR codes issued before step 4 are now invalid and must be re-issued.
     */
    private static function getPrimaryKey(): string
    {
        $key = env('QR_ENCRYPTION_KEY');
        if (!$key) {
            throw new \RuntimeException('QR_ENCRYPTION_KEY not configured. Set it in .env to a dedicated base64-encoded 32-byte value.');
        }
        return base64_decode($key);
    }

    private static function getRotationKey(): ?string
    {
        $key = env('QR_ENCRYPTION_KEY_NEW');
        if (!$key) {
            return null;
        }

        $rotationAt = env('QR_ENCRYPTION_KEY_ROTATION_AT');
        if ($rotationAt && now()->greaterThanOrEqualTo($rotationAt)) {
            return null;
        }

        return base64_decode($key);
    }

    private static function keys(): array
    {
        $keys = [self::getPrimaryKey()];

        $rotationKey = self::getRotationKey();
        if ($rotationKey !== null && $rotationKey !== $keys[0]) {
            $keys[] = $rotationKey;
        }

        return $keys;
    }

    /**
     * Encrypt a payload using AES-256-CBC with the primary QR key.
     */
    public static function encrypt(array $payload): string
    {
        $key = self::getPrimaryKey();
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
     * Decrypt a payload using AES-256-CBC.
     *
     * Tries configured keys in order so key rotation does not break in-flight QR codes.
     */
    public static function decrypt(string $encryptedPayload): ?array
    {
        try {
            $data = base64_decode($encryptedPayload);
            if ($data === false || strlen($data) <= 16) {
                return null;
            }

            $iv = substr($data, 0, 16);
            $encrypted = substr($data, 16);

            foreach (self::keys() as $key) {
                $decrypted = openssl_decrypt(
                    $encrypted,
                    'AES-256-CBC',
                    $key,
                    OPENSSL_RAW_DATA,
                    $iv
                );

                if ($decrypted !== false) {
                    $decoded = json_decode($decrypted, true);
                    if (is_array($decoded)) {
                        return $decoded;
                    }
                }
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning('QR decryption failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate HMAC signature for QR payload using the primary key.
     */
    public static function sign(array $payload): string
    {
        $key = self::getPrimaryKey();
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
