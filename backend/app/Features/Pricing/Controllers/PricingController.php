<?php

namespace App\Features\Pricing\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Features\Pricing\Models\PricingWindow;
use App\Features\Pricing\Resources\PricingWindowResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PricingController extends Controller
{
    /**
     * GET /api/events/{event}/pricing — Attendee-facing current pricing.
     *
     * Returns only active pricing windows for a published event.
     * Grouped by ticket category (ticket tier).
     * Does not require authentication.
     */
    public function show(Request $request, $eventId): JsonResponse
    {
        $event = Event::without('analyticsEventsMetric')->find($eventId);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        // Only expose pricing for published events
        if ((string) $event->status !== 'live' && (string) $event->status !== 'published') {
            return response()->json(['message' => 'Pricing not available for this event'], 404);
        }

        // Get currently active windows, ordered by priority then start date
        $windows = PricingWindow::forEvent($eventId)
            ->active()
            ->with(['ticketTier'])
            ->prioritized()
            ->get();

        // Group by ticket category
        $grouped = $windows->groupBy('ticket_category_id')->map(function ($group) {
            return PricingWindowResource::collection($group);
        });

        return response()->json([
            'event_id' => (string) $event->id,
            'event_title' => $event->title,
            'status' => $event->status,
            'pricing_windows' => $grouped,
            'total_windows' => $windows->count(),
        ]);
    }
}
