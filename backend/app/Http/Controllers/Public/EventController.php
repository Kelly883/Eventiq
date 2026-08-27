<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventPublicResource;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Backs the homepage and any anonymous/public event browsing.
 * frontend/src/features/homepage/hooks/useHomepageData.js already called
 * GET /events (with ?sort=trending, ?sort=upcoming, ?limit=N) and
 * GET /categories -- neither route existed anywhere in this app, so every
 * homepage data fetch was a 404. Every section silently rendered nothing
 * (each one's isError/empty check returns null rather than throwing), so
 * the homepage never visibly errored -- it just never showed a single real
 * event, invisibly, since whenever those hooks were added.
 */
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
}
