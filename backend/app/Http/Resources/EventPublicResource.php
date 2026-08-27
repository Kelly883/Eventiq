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
 */
class EventPublicResource extends JsonResource
{
    /**
     * Field names here are deliberately chosen to match what
     * TrendingSection.jsx and UpcomingEventsSection.jsx already expect --
     * both were already written against image_url/start_date/organizer
     * (string)/venue (string)/location, with defensive `a?.b || a` fallback
     * chains suggesting real uncertainty about the eventual contract on the
     * frontend side too. Matching that existing, already-written contract
     * rather than picking new names and requiring changes across multiple
     * frontend files.
     *
     * organizer is returned as a plain display-name string, not a nested
     * object: both components access it as `event.organizer?.name ||
     * event.organizer`, which means an object without a `.name` key (what
     * this used to return, via OrganizerPublicResource, keyed as
     * `displayName` not `name`) falls through to rendering the raw object
     * as a React child -- a real crash ("Objects are not valid as a React
     * child"), not just a display bug, the moment organizer data is
     * actually present. A flat string satisfies the existing fallback
     * pattern safely and also further narrows what this public,
     * unauthenticated endpoint exposes about an organizer to just their
     * public-facing display name.
     */
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
            'start_date' => $this->start_datetime,
            'end_date' => $this->end_datetime,
            'venue' => $this->venue_name,
            'location' => $this->venue_address,
            'category' => $this->category,
            'image_url' => $this->banner_image_url,
            'ticket_price' => $lowestPrice,
            'tickets_remaining' => $ticketsRemaining,
            'organizer' => $this->whenLoaded('organizer', function () {
                return $this->organizer->getPublicProfile()['displayName'] ?? null;
            }),
        ];
    }
}
