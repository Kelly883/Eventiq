<?php

namespace App\Features\QRCodeTicketing\Controllers;

use App\Features\Checkout\Models\Ticket;
use App\Features\QRCodeTicketing\Services\QRCodeService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserQRGenerationController extends Controller
{
    public function __construct(private readonly QRCodeService $qrCodeService)
    {
    }

    /**
     * POST /api/tickets/generate-qr
     *
     * Ticket owners generate a QR code for their ticket.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'ticket_id' => ['required', 'string', 'exists:tickets,id'],
        ]);

        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        $ticket = Ticket::with(['event', 'order'])->find($validated['ticket_id']);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        // Ownership check - only the ticket owner can generate QR
        if ((string) $ticket->user_id !== (string) $user->id && !$user->hasRole('admin')) {
            return response()->json(['message' => 'You can only generate QR codes for your own tickets.'], 403);
        }

        // Check if ticket is valid
        if (!in_array($ticket->status, ['valid', 'checked_in'], true)) {
            return response()->json([
                'message' => 'Cannot generate QR code for a ' . $ticket->status . ' ticket.',
            ], 422);
        }

        try {
            $encryptedPayload = $this->qrCodeService->generateForTicket($ticket);

            // Update ticket with QR data
            $qrCodeExpiresAt = now()->addDays(30);
            $ticket->update([
                'qr_code_data' => $encryptedPayload,
                'qr_code_generated_at' => now(),
                'qr_code_expires_at' => $qrCodeExpiresAt,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'QR code generated successfully.',
                'data' => [
                    'ticket_id' => $ticket->id,
                    'qr_code_data' => $encryptedPayload,
                    'expires_at' => $qrCodeExpiresAt->toDateTimeString(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('User QR generation failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to generate QR code.'], 500);
        }
    }
}
