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
        // CategorySection.jsx expects {id, slug, name, events_count} per
        // category -- this used to return a flat array of raw strings.
        // With the previous fix (this endpoint existing at all) actually
        // shipped, that mismatch would have gone from "section invisible"
        // to "section visible but broken": category.id/name/events_count
        // are all undefined on a string, rendering blank names, a literal
        // "/events?category=undefined" link, and "0 events" on every card.
        //
        // There's no dedicated Category model -- category is a plain
        // string column on events -- so id/slug both use the raw stored
        // value (needed for scopeByCategory's exact-match filtering to
        // keep working via ?category=<slug>); name is a display-only
        // title-cased version. events_count is a real per-category count,
        // not decorative.
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
