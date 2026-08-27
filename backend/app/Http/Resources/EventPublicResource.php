<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Deliberately separate from EventResource, which embeds
 * OrganizerPrivateResource and is meant for the authenticated organizer
 * managing their own event -- not for anonymous browsing. Raw ticket tiers
 * aren't exposed here either (TicketTierResource is currently an
 * unfiltered passthrough of every model attribute, including
 * voucher_code) -- only the aggregate price/availability figures an
 * anonymous visitor actually needs are computed and returned.
 *
 * ticket_tiers is the one exception: a *safe* subset (id, name, price,
 * is_available) is returned for the public event detail page so the UI can
 * render the tier selector without leaking voucher codes.
 */
class EventPublicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // isAvailable() alone doesn't check publication status -- a tier an
        // organizer is still drafting (status != 'published') shouldn't
        // influence what an anonymous visitor sees on the homepage, even if
        // it happens to be active with quantity remaining.
        $tiers = $this->whenLoaded('ticketTiers', fn () => $this->ticketTiers, collect())
            ->filter(fn ($tier) => $tier->status === 'published');
        $availableTiers = $tiers->filter(fn ($tier) => $tier->isAvailable());

        $ticketsRemaining = null;
        if ($tiers->isNotEmpty()) {
            $unlimited = $tiers->contains(fn ($tier) => $tier->quantity === null && $tier->is_active);
            $ticketsRemaining = $unlimited
                ? null
                : $availableTiers->sum(fn ($tier) => $tier->available_count ?? 0);
        }

        $lowestPrice = $availableTiers->isNotEmpty()
            ? $availableTiers->min(fn ($tier) => $tier->getEffectivePrice())
            : null;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'start_datetime' => $this->start_datetime,
            'end_datetime' => $this->end_datetime,
            'venue_name' => $this->venue_name,
            'venue_address' => $this->venue_address,
            'category' => $this->category,
            'banner_image_url' => $this->banner_image_url,
            'ticket_price' => $lowestPrice,
            'tickets_remaining' => $ticketsRemaining,
            'ticket_tiers' => $tiers->map(function ($tier) {
                return [
                    'id' => $tier->id,
                    'name' => $tier->name,
                    'price' => $tier->getEffectivePrice(),
                    'currency' => $tier->currency ?? null,
                    'is_available' => $tier->isAvailable(),
                ];
            })->values(),
            'organizer' => $this->whenLoaded('organizer', function () {
                return new OrganizerPublicResource($this->organizer);
            }),
        ];
    }
}
