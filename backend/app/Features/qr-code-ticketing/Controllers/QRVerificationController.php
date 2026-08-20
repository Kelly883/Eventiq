<?php

namespace App\Features\QRCodeTicketing\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class QRVerificationController extends Controller
{
    /**
     * Decrypt and verify an incoming ticket QR code payload.
     * 
     * POST /api/venue/check-in/qr
     */
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'encrypted_payload' => ['required', 'string'],
        ]);

        try {
            // Decrypt the payload
            $decryptedRaw = Crypt::decryptString($validated['encrypted_payload']);
            $payload = json_decode($decryptedRaw, true);

            if (!$payload || !isset($payload['ticket_id']) || !isset($payload['event_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR payload format.',
                ], 422);
            }

            // Verify HMAC signature to protect against tampering
            $expectedSignature = hash_hmac('sha256', "{$payload['event_id']}-{$payload['ticket_id']}", config('app.key'));
            if (!hash_equals($expectedSignature, $payload['signature'] ?? '')) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR Code signature mismatch. Counterfeit attempt suspected.',
                ], 403);
            }

            // At this point the ticket is decrypted and verified authentic
            // Let's return the ticket data so checking in can complete
            return response()->json([
                'success' => true,
                'message' => 'Ticket decrypted and verified successfully.',
                'data' => [
                    'ticket_id' => $payload['ticket_id'],
                    'event_id' => $payload['event_id'],
                    'generated_at' => $payload['generated_at'],
                    'verified_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            Log::warning('QR Code Decryption Failure: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to decrypt QR code payload. It may be corrupted or forged.',
            ], 400);
        } catch (\Exception $e) {
            Log::error('QR Verification General Failure: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal validation failure.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

