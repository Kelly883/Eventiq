<?php

namespace App\Features\EventsCalendar\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CalendarEventResource — public calendar event representation.
 *
 * Strips all sensitive organizer data. Only exposes:
 *   - organizer.id, displayName, avatarUrl
 *
 * Never expose: paystack_subaccount_code, paystack_recipient_code,
 * flutterwave_subaccount_id, commissionRate, email, phone, payoutMethods,
 * transactions, apiKeys, etc.
 */
class CalendarEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $resource = $this->resource;

        return [
            'id' => $resource->id,
            'organizer_id' => $resource->organizer_id,
            'title' => $resource->title,
            'start_datetime' => $resource->start_datetime,
            'end_datetime' => $resource->end_datetime,
            'venue_name' => $resource->venue_name,
            'venue_address' => $resource->venue_address,
            'status' => $resource->status,
            'category' => $resource->category,
            'capacity' => $resource->capacity,
            'total_available' => (int) $resource->total_available,
            'total_sold' => (int) $resource->total_sold,
            'min_price' => $resource->min_price !== null ? (float) $resource->min_price : null,
            'max_price' => $resource->max_price !== null ? (float) $resource->max_price : null,
            'popularity' => (int) $resource->popularity,
            'availability_status' => $this->computeAvailabilityStatus($resource),
            'organizer' => $resource->organizer ? [
                'id' => $resource->organizer->id,
                'displayName' => $resource->organizer->displayName,
                'avatarUrl' => $resource->organizer->avatarUrl,
            ] : null,
        ];
    }

    private function computeAvailabilityStatus($resource): string
    {
        $totalAvailable = (int) ($resource->total_available ?? 0);
        $totalSold = (int) ($resource->total_sold ?? 0);
        $lowStockThreshold = (int) ($resource->low_stock_threshold ?? 10);

        // No inventory rows configured at all -> treat as unavailable.
        if ($totalAvailable === 0 && $totalSold === 0) {
            return 'unavailable';
        }

        if ($totalAvailable <= 0) {
            return 'sold_out';
        }

        if ($totalAvailable <= $lowStockThreshold) {
            return 'low_stock';
        }

        return 'available';
    }
}
