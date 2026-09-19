<?php

namespace App\Features\Ticketing\Controllers;

use App\Features\Ticketing\Requests\UpdateTicketTiersRequest;
use App\Features\Ticketing\Resources\TicketTierResource;
use App\Features\Ticketing\Services\TicketTierService;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        $idempotencyKey = $request->header('Idempotency-Key');
        $idempotencyCacheKey = null;
        if ($idempotencyKey) {
            $idempotencyCacheKey = 'event:ticketing:update:' . $user->id . ':' . $event->id . ':' . sha1($idempotencyKey);
            if ($cached = Cache::get($idempotencyCacheKey)) {
                return response()->json($cached, 200);
            }
        }

        try {
            $updatedTiers = DB::transaction(function () use ($event, $tiers, $user, $request) {
                // Use service to sync (passes user/request for per-tier audit logging)
                return $this->tierService->syncTiers($event->id, $tiers, $user, $request);
            });

            $event->load(['ticketTiers', 'organizer']);
            // Also load via service result to ensure fresh
            $event->setRelation('ticketTiers', $updatedTiers);

            $createdTiers = collect($updatedTiers)->filter(fn ($t) => $t->wasRecentlyCreated)->count();
            $deletedTiers = collect($tiers)->filter(fn ($t) => empty($t['id']))->count();

            AuditLogger::forEvent(
                action: 'ticket_tier.updated',
                user: $user,
                eventId: (string) $event->id,
                newValues: [
                    'tiers_synced' => count($updatedTiers),
                    'created' => $createdTiers,
                    'deleted' => $deletedTiers,
                ],
                request: $request,
                description: "Ticket tiers synced for event '{$event->title}'"
            );

            $response = response()->json([
                'message' => 'Event and ticket tiers updated successfully',
                'data' => [
                    'event' => [
                        'id' => $event->id,
                        'title' => $event->title,
                    ],
                    'ticketTiers' => TicketTierResource::collection($updatedTiers),
                ],
            ], 200);

            if ($idempotencyCacheKey) {
                try {
                    Cache::put($idempotencyCacheKey, $response->getData(true), 86400);
                } catch (\Throwable $e) {}
            }

            return $response;
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Ticket tier sync failed', ['event_id' => $eventId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Failed to update ticket tiers', 'error' => $e->getMessage()], 500);
        }
    }
}
