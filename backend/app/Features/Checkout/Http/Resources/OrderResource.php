<?php

namespace App\Features\Checkout\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'payment_gateway' => $this->payment_gateway,
            'event' => $this->whenLoaded('event', fn () => [
                'id' => $this->event->id,
                'title' => $this->event->title,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'ticket_tier' => $item->ticketTier->name ?? null,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ])),
            'ticket_count' => $this->whenLoaded('tickets', fn () => $this->tickets->count()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
