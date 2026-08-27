<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventPublicResource;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = min((int) $request->integer('limit', 20), 50);

        $query = Event::published()
            ->with(['organizer', 'ticketTiers']);

        $sort = $request->query('sort');

        if ($sort === 'upcoming') {
            $query->upcoming()->upcomingFirst();
        } elseif ($sort === 'trending') {
            // No dedicated trending signal exists yet (e.g. view counts,
            // recent sales velocity) -- available()/ticket demand nearest
            // in time is the closest reasonable proxy today, rather than
            // inventing a metric this app doesn't actually track.
            $query->available()->upcomingFirst();
        } else {
            $query->upcomingFirst();
        }

        if ($category = $request->query('category')) {
            $query->byCategory($category);
        }

        $search = $request->query('search') ?: $request->query('q');
        if (is_string($search) && trim($search) !== '') {
            $query->search(trim($search));
        }

        $filter = $request->query('filter');
        if (is_string($filter) && $filter !== '') {
            if ($filter === 'popular') {
                $query->available()->upcomingFirst();
            } elseif (in_array($filter, ['today', 'week', 'month'], true)) {
                $query->withinWindow($filter);
            }
        }

        $events = $query->limit($limit)->get();

        return EventPublicResource::collection($events);
    }

    public function categories(): \Illuminate\Http\JsonResponse
    {
        $categories = Event::published()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->filter(fn ($c) => trim((string) $c) !== '')
            ->values();

        return response()->json(['data' => $categories]);
    }

    /**
     * GET /api/events/{event}
     *
     * PRD: discovery_endpoint_detail -- public event detail page.
     * A visitor may only ever reach a published event (Event::published()).
     */
    public function show(Request $request, int $eventId): EventPublicResource
    {
        $event = Event::published()
            ->with(['organizer', 'ticketTiers'])
            ->findOrFail($eventId);

        return new EventPublicResource($event);
    }
}
