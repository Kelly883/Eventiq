<?php

namespace App\Features\OfflineSync\Resources;

use App\Features\Ticketing\Models\Ticket;
use Illuminate\Http\Resources\Json\JsonResource;

class OfflineTicketResource extends JsonResource
{
    public function toArray($request): array
    {
        $ticket = $this->resource;

        return [
            'id' => (string) $ticket->id,
            'eventId' => (string) $ticket->event_id,
            'eventName' => $ticket->event->title ?? null,
            'eventStartDate' => $ticket->event->start_datetime?->toIso8601String(),
            'eventUpdatedAt' => $ticket->event->updated_at?->toIso8601String(),
            'ticketTierId' => (string) $ticket->ticket_tier_id,
            'tierName' => $ticket->ticketTier->name ?? null,
            'tierUpdatedAt' => $ticket->ticketTier->updated_at?->toIso8601String(),
            'qrCodeData' => $ticket->qr_code_data,
            'orderId' => (string) $ticket->order_id,
            'orderNumber' => $ticket->order?->payment_intent_id ?? null,
            'attendeeName' => $ticket->attendee_name ?? null,
            'attendeeEmail' => $ticket->attendee_email ?? null,
            'status' => $ticket->status ?? 'valid',
            'checkedInAt' => $ticket->checked_in_at?->toIso8601String(),
            'qrCodeExpiresAt' => $ticket->qr_code_expires_at?->toIso8601String(),
            'ticketType' => $ticket->tier ?? null,
            'pricePaid' => $ticket->ticketTier->price ?? null,
            'paymentStatus' => $ticket->order?->status ?? null,
            'barcodeType' => 'qr',
        ];
    }
}
