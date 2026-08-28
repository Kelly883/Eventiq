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
     * Requires the caller to be authenticated (auth:sanctum) and to be a
     * venue staff member (admin or organizer) who owns the event in the
     * decrypted QR payload.
     *
     * POST /api/venue/check-in/qr
     *
     * @throws 401  Unauthenticated
     * @throws 403  Not a venue staff member OR event in QR payload is not owned by this user
     * @throws 422  Malformed payload
     * @throws 400  Decryption failed (corrupt or forged payload)
     */
    public function verify(Request $request)
    {
        // 1. Authenticate — auth:sanctum middleware already ran on the route
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        // 2. Authorize venue staff role
        if (!$user->hasRole('admin') && !$user->hasRole('organizer')) {
            abort(403, 'Only venue staff (admin or organizer) may verify QR codes.');
        }

        $validated = $request->validate([
            'encrypted_payload' => ['required', 'string'],
        ]);

        try {
            // 3. Decrypt the payload
            $decryptedRaw = Crypt::decryptString($validated['encrypted_payload']);
            $payload = json_decode($decryptedRaw, true);

            if (!$payload || !isset($payload['ticket_id']) || !isset($payload['event_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR payload format.',
                ], 422);
            }

            // 4. Verify HMAC signature to protect against tampering
            $expectedSignature = hash_hmac('sha256', "{$payload['event_id']}-{$payload['ticket_id']}", config('app.key'));
            if (!hash_equals($expectedSignature, $payload['signature'] ?? '')) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR Code signature mismatch. Counterfeit attempt suspected.',
                ], 403);
            }

            // 5. Verify event ownership — venue staff can only read QR codes
            //    for events they own (admins bypass this check)
            $eventId = $payload['event_id'];
            if (!$user->hasRole('admin')) {
                $eventModel = \App\Models\Event::find($eventId);
                if (!$eventModel) {
                    abort(404, 'Event not found.');
                }
                if ($eventModel->organizer_id !== $user->id) {
                    abort(403, 'You do not have permission to verify tickets for this event.');
                }
            }

            // At this point the ticket is decrypted, verified authentic,
            // and the venue staff is authorized to handle it.
            return response()->json([
                'success' => true,
                'message' => 'Ticket decrypted and verified successfully.',
                'data' => [
                    'ticket_id' => $payload['ticket_id'],
                    'event_id' => $payload['event_id'],
                    'generated_at' => $payload['generated_at'] ?? null,
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
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
