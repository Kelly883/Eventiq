<?php

namespace App\Features\Public\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Features\Public\Resources\EventResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|\Illuminate\Http\JsonResponse
    {
        $query = Event::published()
            ->with(['organizer', 'ticketTiers', 'analyticsEventsMetric']);

        $search = $request->query('search') ?: $request->query('q');
        if (is_string($search) && trim($search) !== '') {
            if (strlen(trim($search)) < 2) {
                return response()->json(['message' => 'Search query must be at least 2 characters.'], 400);
            }
            $query->search(trim($search));
        }

        if ($category = $request->query('category')) {
            $query->byCategory($category);
        }

        $sort = $request->query('sort');
        if ($sort === 'upcoming') {
            $query->upcoming()->upcomingFirst();
        } elseif ($sort === 'trending') {
            $query->available()->upcomingFirst();
        } else {
            $query->upcomingFirst();
        }

        $perPage = min((int) $request->integer('per_page', 20), 50);
        $events = $query->paginate($perPage);

        return EventResource::collection($events);
    }

    public function show(Event $event): EventResource
    {
        if ($event->status !== 'published') {
            abort(404);
        }

        $event->load(['organizer', 'ticketTiers', 'analyticsEventsMetric']);

        return new EventResource($event);
    }

    public function categories(): JsonResponse
    {
        $counts = Event::published()
            ->whereNotNull('category')
            ->selectRaw('category, count(*) as events_count')
            ->groupBy('category')
            ->orderBy('category')
            ->get()
            ->filter(fn ($row) => trim((string) $row->category) !== '')
            ->map(fn ($row) => [
                'id' => $row->category,
                'slug' => $row->category,
                'name' => ucwords($row->category),
                'events_count' => $row->events_count,
            ])
            ->values();

        return response()->json(['data' => $counts]);
    }

    public function ticketTiers(Event $event): JsonResponse
    {
        if ($event->status !== 'published') {
            abort(404);
        }

        $tiers = $event->ticketTiers()
            ->where('status', 'published')
            ->get()
            ->map(fn ($tier) => [
                'id' => $tier->id,
                'name' => $tier->name,
                'price' => $tier->price,
                'currency' => $tier->currency,
                'quantity' => $tier->quantity,
                'sold_count' => $tier->sold_count,
                'is_active' => $tier->is_active,
                'available_count' => $tier->available_count,
            ]);

        return response()->json(['data' => $tiers]);
    }

    public function pricingWindows(Event $event): JsonResponse
    {
        if ($event->status !== 'published') {
            abort(404);
        }

        $windows = $event->pricingWindows()
            ->where('is_active', true)
            ->get()
            ->map(fn ($window) => [
                'id' => $window->id,
                'window_name' => $window->window_name,
                'start_date_time' => $window->start_date_time,
                'end_date_time' => $window->end_date_time,
                'price' => $window->price,
                'quantity_limit' => $window->quantity_limit,
                'quantity_sold' => $window->quantity_sold,
                'available_quantity' => $window->available_quantity,
            ]);

        return response()->json(['data' => $windows]);
    }

    public function analytics(Event $event): JsonResponse
    {
        if ($event->status !== 'published') {
            abort(404);
        }

        $metric = $event->analyticsEventsMetric;

        return response()->json([
            'data' => [
                'event_id' => $event->id,
                'popularity_score' => $metric?->total_page_views ?? 0,
                'total_tickets_sold' => $metric?->total_tickets_sold ?? 0,
                'total_revenue' => $metric?->total_revenue ?? 0,
            ],
        ]);
    }

    public function availability(Event $event): JsonResponse
    {
        if ($event->status !== 'published') {
            abort(404);
        }

        $tiers = $event->ticketTiers()->where('status', 'published')->get();
        $totalSold = $tiers->sum('sold_count');
        $totalCapacity = $event->capacity ?? $tiers->sum('quantity');
        $availability = $totalCapacity !== null ? max(0, $totalCapacity - $totalSold) : null;

        return response()->json([
            'data' => [
                'event_id' => $event->id,
                'total_capacity' => $totalCapacity,
                'total_sold' => $totalSold,
                'availability' => $availability,
                'is_available' => $availability === null || $availability > 0,
            ],
        ]);
    }
}
