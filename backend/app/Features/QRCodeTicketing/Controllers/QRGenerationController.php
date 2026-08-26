<?php

namespace App\Features\QRCodeTicketing\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Log;

class QRGenerationController extends Controller
{
    /**
     * Generate an encrypted QR Code for a given ticket.
     * 
     * POST /api/organizer/events/{event}/tickets/{ticket}/qr
     */
    public function generate(Request $request, $eventId, $ticketId)
    {
        $user = $request->user();
        $eventId = (int) $eventId;
        $ticketId = (int) $ticketId;

        // Only the event's owning organizer (or an admin) may mint signed QR codes.
        $event = \App\Models\Event::with('organizer')->find($eventId);
        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found.',
            ], 404);
        }

        $ownsEvent = $event->organizer
            && (string) $event->organizer->user_id === (string) $user->id;
        if (!$ownsEvent && !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to generate QR codes for this event.',
            ], 403);
        }

        // The ticket must belong to the event being claimed.
        $ticketExists = \App\Features\Checkout\Models\Ticket::where('id', $ticketId)
            ->where('event_id', $eventId)
            ->exists();
        if (!$ticketExists) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found for this event.',
            ], 404);
        }

        try {
            // Build the payload with ticket/event properties
            $payload = [
                'ticket_id' => $ticketId,
                'event_id' => $eventId,
                'scanned_count' => 0,
                'generated_at' => now()->toIso8601String(),
                'signature' => hash_hmac('sha256', "{$eventId}-{$ticketId}", config('app.key')),
            ];

            // Encrypt using AES-256 via Crypt facade
            $encryptedPayload = Crypt::encryptString(json_encode($payload));

            // Generate QR Code SVG or PNG base64 string
            // We use simple-qrcode's standard api
            $qrSvg = QrCode::size(300)
                ->color(79, 70, 229) // Indigo brand color
                ->backgroundColor(255, 255, 255)
                ->margin(1)
                ->generate($encryptedPayload);

            // In real app, write QR image to disk/storage
            $filename = "qrcodes/ticket_{$ticketId}_qr.svg";
            Storage::disk('public')->put($filename, $qrSvg);
            $qrUrl = Storage::disk('public')->url($filename);

            // Return response
            return response()->json([
                'success' => true,
                'message' => 'QR Code generated and encrypted successfully.',
                'data' => [
                    'ticket_id' => $ticketId,
                    'event_id' => $eventId,
                    'encrypted_payload' => $encryptedPayload,
                    'qr_code_url' => $qrUrl,
                    'qr_svg_raw' => base64_encode($qrSvg),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('QR Code Generation Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate secure QR Code.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

