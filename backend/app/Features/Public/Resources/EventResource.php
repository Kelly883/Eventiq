<?php

namespace App\Features\Public\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tiers = $this->resource->ticketTiers ?? collect();
        $totalSold = $tiers->sum('sold_count');
        $ticketAvailability = $this->resource->capacity !== null
            ? max(0, $this->resource->capacity - $totalSold)
            : null;

        $popularityScore = optional($this->resource->analyticsEventsMetric)->total_page_views ?? 0;

        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'slug' => $this->resource->slug,
            'description' => $this->resource->description,
            'start_datetime' => $this->resource->start_datetime,
            'end_datetime' => $this->resource->end_datetime,
            'status' => $this->resource->status,
            'category' => $this->resource->category,
            'organizer' => $this->resource->organizer ? [
                'name' => $this->resource->organizer->displayName ?? null,
                'avatar' => $this->resource->organizer->avatarUrl ?? null,
            ] : null,
            'ticket_tiers' => $tiers->map(fn ($tier) => [
                'id' => $tier->id,
                'name' => $tier->name,
                'price' => $tier->price,
                'currency' => $tier->currency,
                'quantity' => $tier->quantity,
                'sold_count' => $tier->sold_count,
                'is_active' => $tier->is_active,
                'status' => $tier->status,
            ]),
            'ticket_availability' => $ticketAvailability,
            'popularity_score' => $popularityScore,
        ];
    }
}
