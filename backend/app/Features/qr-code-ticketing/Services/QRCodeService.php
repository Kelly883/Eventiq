<?php

namespace App\Features\QRCodeTicketing\Services;

use App\Features\Checkout\Models\Ticket;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Reusable QR generation logic, extracted so both ticket-issuance-on-
 * webhook (this file's consumer) and QRGenerationController's on-demand
 * regeneration endpoint use the same encryption/signature approach.
 * NOTE: QRGenerationController itself wasn't refactored to call this (it
 * already worked; kept the change scope smaller) - the crypto logic here
 * mirrors it but isn't literally shared code between the two yet.
 */
class QRCodeService
{
    public function generateForTicket(Ticket $ticket): string
    {
        $payload = [
            'ticket_id' => $ticket->id,
            'event_id' => $ticket->event_id,
            'scanned_count' => 0,
            'generated_at' => now()->toIso8601String(),
            'signature' => hash_hmac('sha256', "{$ticket->event_id}-{$ticket->id}", config('app.key')),
        ];

        $encryptedPayload = Crypt::encryptString(json_encode($payload));

        try {
            $qrSvg = QrCode::size(300)
                ->color(79, 70, 229)
                ->backgroundColor(255, 255, 255)
                ->margin(1)
                ->generate($encryptedPayload);

            Storage::disk('public')->put("qrcodes/ticket_{$ticket->id}_qr.svg", $qrSvg);
        } catch (\Throwable $e) {
            // Don't block ticket issuance on QR image rendering - the
            // encrypted payload (stored below) is what actually matters
            // for check-in; the SVG is a display convenience.
            Log::error('QRCodeService: SVG generation failed for ticket ' . $ticket->id . ': ' . $e->getMessage());
        }

        return $encryptedPayload;
    }
}
