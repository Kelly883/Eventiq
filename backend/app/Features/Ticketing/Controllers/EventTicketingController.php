<?php

namespace App\Features\Ticketing\Controllers;

use App\Features\Ticketing\Requests\UpdateTicketTiersRequest;
use App\Features\Ticketing\Resources\TicketTierResource;
use App\Features\Ticketing\Services\TicketTierService;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class EventTicketingController extends Controller
{
    public function __construct(private TicketTierService $tierService) {}

    /**
     * PUT/PATCH /api/organizer/events/{event}/ticketing
     * Also handles PATCH /api/organizer/events/{eventId} with ticketTiers for spec compat
     */
    public function update(UpdateTicketTiersRequest $request, $eventId = null)
    {
        // Resolve event id from route param (supports {event} and {eventId})
        $eventId = $eventId ?? $request->route('event') ?? $request->route('eventId');
        // Also handle case where route param is named differently
        if (!$eventId) {
            $eventId = $request->route('event');
        }

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Find event
        $event = Event::without('analyticsEventsMetric')->find($eventId);
        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        // Check authorization — must be event organizer
        try {
            Gate::forUser($user)->authorize('view', $event);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'Forbidden — you are not the event organizer'], 403);
        }
        // Also check update permission explicitly
        try {
            Gate::forUser($user)->authorize('update', $event);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'Forbidden — you are not the event organizer'], 403);
        }

        $validated = $request->validated();
        $tiers = $validated['ticketTiers'] ?? $validated['tiers'] ?? [];

        try {
            $updatedTiers = DB::transaction(function () use ($event, $tiers) {
                // Use service to sync
                return $this->tierService->syncTiers($event->id, $tiers);
            });

            $event->load(['ticketTiers', 'organizer']);
            // Also load via service result to ensure fresh
            $event->setRelation('ticketTiers', $updatedTiers);

            return response()->json([
                'message' => 'Event and ticket tiers updated successfully',
                'data' => [
                    'event' => [
                        'id' => $event->id,
                        'title' => $event->title,
                    ],
                    'ticketTiers' => TicketTierResource::collection($updatedTiers),
                    'tiers' => TicketTierResource::collection($updatedTiers),
                ],
                'ticketTiers' => TicketTierResource::collection($updatedTiers),
                'tiers' => TicketTierResource::collection($updatedTiers),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Ticket tier sync failed', ['event_id' => $eventId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Failed to update ticket tiers', 'error' => $e->getMessage()], 500);
        }
    }
}
