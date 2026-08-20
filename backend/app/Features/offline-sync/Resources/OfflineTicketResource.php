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
            'ticketTierId' => (string) $ticket->ticket_tier_id,
            'tierName' => $ticket->ticketTier->name ?? null,
            'qrCodeData' => (string) $ticket->qr_code_data,
            'orderId' => (string) $ticket->order_id,
            'orderNumber' => $ticket->order?->payment_intent_id ?? null,
        ];
    }
}
