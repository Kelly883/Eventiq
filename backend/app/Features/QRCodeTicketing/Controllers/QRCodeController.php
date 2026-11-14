<?php

namespace App\Features\QRCodeTicketing\Controllers;

use App\Features\Checkout\Models\Ticket;
use App\Features\CheckIn\Models\AuditLog;
use App\Features\QRCodeTicketing\Services\QRCodeService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class QRCodeController extends Controller
{
    public function __construct(private readonly QRCodeService $qrCodeService) {}

    /**
     * POST /api/tickets/verify-qr
     * Verify an encrypted QR payload and return ticket details.
     */
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'qr_code_data' => ['required', 'string'],
        ]);

        try {
            $payload = $this->qrCodeService->decrypt($validated['qr_code_data']);
        } catch (\Throwable $e) {
            Log::warning('QR verification failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Invalid QR code.'], 422);
        }

        $ticketId = $payload['ticket_id'] ?? null;
        $nonce    = $payload['nonce'] ?? null;

        $ticket = Ticket::with(['event', 'ticketTier'])
            ->where('id', $ticketId)
            ->first();

        if (! $ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        // Expiry check
        if ($ticket->qr_code_expires_at && now()->gt($ticket->qr_code_expires_at)) {
            return response()->json(['message' => 'QR code expired.'], 410);
        }

        // Status checks
        if ($ticket->status === 'void') {
            return response()->json(['message' => 'This ticket is invalid.'], 422);
        }
        if ($ticket->status === 'checked_in') {
            return response()->json([
                'message' => 'Already checked in at ' . $ticket->checked_in_at->format('H:i'),
            ], 200);
        }

        // Fraud flag
        if ($ticket->fraudEvents()->exists()) {
            return response()->json(['message' => 'Ticket flagged for fraud.'], 403);
        }

        // Audit log
        AuditLog::create([
            'event_id' => $ticket->event_id,
            'user_id'  => $request->user()->id,
            'action'   => 'verify_qr',
            'ticket_id'=> $ticket->id,
            'details'  => ['nonce' => $nonce],
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'ticket_id'   => $ticket->ticket_id,
                'status'      => $ticket->status,
                'event'       => $ticket->event,
                'tier'        => $ticket->ticketTier,
                'expires_at'  => $ticket->qr_code_expires_at?->toIso8601String(),
            ],
        ]);
    }
}
?>