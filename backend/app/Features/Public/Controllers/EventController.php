<?php

namespace App\Features\Public\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Features\Pricing\Models\PricingWindow;
use App\Features\Public\Resources\EventResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EventController extends Controller
{
    /**
     * GET /api/events/public/list
     * GET /api/public/events
     * Paginated published events with filtering and sorting.
     *
     * Query params:
     *   search|q         — search query (min 2 chars)
     *   category         — filter by category
     *   date_range       — upcoming, today, week, month, or custom
     *   start_date       — custom start date (ISO 8601)
     *   end_date         — custom end date (ISO 8601)
     *   min_price        — minimum ticket price
     *   max_price        — maximum ticket price
     *   sort             — date, price_asc, price_desc, popularity
     *   per_page         — results per page (max 50)
     *   page             — page number
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $query = Event::published()
            ->with(['organizer', 'ticketTiers', 'analyticsEventsMetric']);

        // Search
        $search = $request->query('search') ?: $request->query('q');
        if (is_string($search) && trim($search) !== '') {
            if (strlen(trim($search)) < 2) {
                return response()->json(['message' => 'Search query must be at least 2 characters.'], 400);
            }
            $query->search(trim($search));
        }

        // Category filter
        if ($category = $request->query('category')) {
            $query->byCategory($category);
        }

        // Date range filter
        $dateRange = $request->query('date_range');
        if ($dateRange) {
            $this->applyDateRange($query, $dateRange, $request);
        }

        // Price range filter
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        if ($minPrice !== null || $maxPrice !== null) {
            $this->applyPriceRange($query, $minPrice, $maxPrice);
        }

        // Sorting
        $sort = $request->query('sort', 'date');
        $this->applySorting($query, $sort);

        $perPage = min((int) $request->integer('per_page', 20), 50);
        $events = $query->paginate($perPage);

        return EventResource::collection($events);
    }

    /**
     * GET /api/events/public/search
     * Autocomplete suggestions for queries >= 2 chars.
     */
    public function search(Request $request): JsonResponse
    {
        $q = $request->query('q');

        if (!is_string($q) || strlen(trim($q)) < 2) {
            return response()->json(['message' => 'Search query must be at least 2 characters.'], 400);
        }

        $results = Event::published()
            ->search(trim($q))
            ->select('id', 'title', 'slug', 'category', 'start_datetime', 'banner_image_url')
            ->limit(10)
            ->get()
            ->map(fn ($event) => [
                'id' => $event->id,
                'title' => $event->title,
                'slug' => $event->slug,
                'category' => $event->category,
                'start_datetime' => $event->start_datetime,
                'banner_image_url' => $event->banner_image_url,
            ]);

        return response()->json(['data' => $results]);
    }

    /**
     * GET /api/events/public/filters
     * Returns available categories and price range.
     */
    public function filters(): JsonResponse
    {
        $categories = Event::published()
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

        $priceRange = DB::table('ticket_tiers')
            ->join('events', 'ticket_tiers.event_id', '=', 'events.id')
            ->where('events.status', 'published')
            ->whereNull('events.deleted_at')
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
            ->first();

        return response()->json([
            'data' => [
                'categories' => $categories,
                'price_range' => [
                    'min' => $priceRange ? (float) $priceRange->min_price : 0,
                    'max' => $priceRange ? (float) $priceRange->max_price : 0,
                ],
            ],
        ]);
    }

    /**
     * GET /api/events/public/{event}
     * GET /api/public/events/{event}
     * Full event details for published events.
     */
    public function show(Event $event): EventResource|JsonResponse
    {
        if ($event->status !== 'published') {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $event->load(['organizer', 'ticketTiers', 'analyticsEventsMetric']);

        return new EventResource($event);
    }

    /**
     * GET /api/events/public/{event}/pricing
     * Current and next pricing info for an event.
     */
    public function pricing(Event $event): JsonResponse
    {
        if ($event->status !== 'published') {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $now = Carbon::now();

        // Find current active pricing window
        $currentWindow = PricingWindow::where('event_id', $event->id)
            ->where('is_active', true)
            ->where('start_date_time', '<=', $now)
            ->where('end_date_time', '>=', $now)
            ->whereNull('deleted_at')
            ->orderBy('priority', 'desc')
            ->first();

        // Find next upcoming pricing window
        $nextWindow = PricingWindow::where('event_id', $event->id)
            ->where('is_active', true)
            ->where('start_date_time', '>', $now)
            ->whereNull('deleted_at')
            ->orderBy('start_date_time', 'asc')
            ->first();

        // Get all active ticket tiers
        $tiers = $event->ticketTiers()
            ->where('status', 'published')
            ->get()
            ->map(fn ($tier) => [
                'id' => $tier->id,
                'name' => $tier->name,
                'price' => (float) $tier->price,
                'currency' => $tier->currency ?? 'USD',
                'quantity' => $tier->quantity,
                'sold_count' => $tier->sold_count,
                'is_active' => $tier->is_active,
                'available_count' => max(0, $tier->quantity - $tier->sold_count),
            ]);

        return response()->json([
            'data' => [
                'event_id' => $event->id,
                'current_pricing' => $currentWindow ? [
                    'window_id' => $currentWindow->id,
                    'window_name' => $currentWindow->window_name,
                    'price' => (float) $currentWindow->price,
                    'start_date_time' => $currentWindow->start_date_time,
                    'end_date_time' => $currentWindow->end_date_time,
                    'available_quantity' => $currentWindow->available_quantity,
                ] : null,
                'next_pricing' => $nextWindow ? [
                    'window_id' => $nextWindow->id,
                    'window_name' => $nextWindow->window_name,
                    'price' => (float) $nextWindow->price,
                    'start_date_time' => $nextWindow->start_date_time,
                    'end_date_time' => $nextWindow->end_date_time,
                ] : null,
                'ticket_tiers' => $tiers,
            ],
        ]);
    }

    /**
     * GET /api/events/public/{event}/related
     * Up to 5 related events from same organizer.
     */
    public function related(Event $event): JsonResponse
    {
        if ($event->status !== 'published') {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $related = Event::published()
            ->where('organizer_id', $event->organizer_id)
            ->where('id', '!=', $event->id)
            ->whereNull('deleted_at')
            ->limit(5)
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'title' => $e->title,
                'slug' => $e->slug,
                'start_datetime' => $e->start_datetime,
                'banner_image_url' => $e->banner_image_url,
                'category' => $e->category,
            ]);

        return response()->json(['data' => $related]);
    }

    /**
     * GET /api/events/public/category/{categoryId}
     * Events filtered by category. Invalid category returns 400.
     */
    public function byCategory(string $categoryId): JsonResponse|AnonymousResourceCollection
    {
        // Validate category exists
        $categoryId = trim(urldecode($categoryId));
        if ($categoryId === '') {
            return response()->json(['message' => 'Invalid category.'], 400);
        }

        $exists = Event::published()
            ->where('category', $categoryId)
            ->whereNull('deleted_at')
            ->exists();

        if (!$exists) {
            return response()->json(['message' => 'No events found for this category.'], 404);
        }

        $events = Event::published()
            ->byCategory($categoryId)
            ->with(['organizer', 'ticketTiers', 'analyticsEventsMetric'])
            ->paginate(20);

        return EventResource::collection($events);
    }

    /**
     * GET /api/public/categories (legacy)
     */
    public function categories(): JsonResponse
    {
        return $this->filters();
    }

    /**
     * GET /api/public/events/{event}/ticket-tiers (legacy)
     */
    public function ticketTiers(Event $event): JsonResponse
    {
        if ($event->status !== 'published') {
            return response()->json(['message' => 'Event not found'], 404);
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

    /**
     * GET /api/public/events/{event}/pricing-windows (legacy)
     */
    public function pricingWindows(Event $event): JsonResponse
    {
        if ($event->status !== 'published') {
            return response()->json(['message' => 'Event not found'], 404);
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

    /**
     * GET /api/public/events/{event}/analytics (legacy)
     */
    public function analytics(Event $event): JsonResponse
    {
        if ($event->status !== 'published') {
            return response()->json(['message' => 'Event not found'], 404);
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

    /**
     * GET /api/public/events/{event}/availability (legacy)
     */
    public function availability(Event $event): JsonResponse
    {
        if ($event->status !== 'published') {
            return response()->json(['message' => 'Event not found'], 404);
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

    // ── Private helpers ───────────────────────────────────────────────

    private function applyDateRange($query, string $dateRange, Request $request): void
    {
        $now = Carbon::now();

        switch ($dateRange) {
            case 'upcoming':
                $query->where('start_datetime', '>', $now);
                break;
            case 'today':
                $query->whereDate('start_datetime', $now->toDateString());
                break;
            case 'week':
                $query->whereBetween('start_datetime', [$now, $now->copy()->addWeek()]);
                break;
            case 'month':
                $query->whereBetween('start_datetime', [$now, $now->copy()->addMonth()]);
                break;
            case 'custom':
                $startDate = $request->query('start_date');
                $endDate = $request->query('end_date');
                if ($startDate) {
                    $query->where('start_datetime', '>=', Carbon::parse($startDate));
                }
                if ($endDate) {
                    $query->where('start_datetime', '<=', Carbon::parse($endDate));
                }
                break;
        }
    }

    private function applyPriceRange($query, $minPrice, $maxPrice): void
    {
        $query->whereHas('ticketTiers', function ($q) use ($minPrice, $maxPrice) {
            if ($minPrice !== null) {
                $q->where('price', '>=', (float) $minPrice);
            }
            if ($maxPrice !== null) {
                $q->where('price', '<=', (float) $maxPrice);
            }
        });
    }

    private function applySorting($query, string $sort): void
    {
        switch ($sort) {
            case 'price_asc':
            case 'price_desc':
                $direction = $sort === 'price_asc' ? 'asc' : 'desc';
                // Use subquery to avoid ambiguous column issues
                $query->addSelect([
                    'min_price' => function ($sub) {
                        $sub->from('ticket_tiers')
                            ->selectRaw('MIN(ticket_tiers.price)')
                            ->whereColumn('ticket_tiers.event_id', 'events.id')
                            ->where('ticket_tiers.status', 'published');
                    },
                ])->orderBy('min_price', $direction);
                break;
            case 'popularity':
                $query->leftJoin('analytics_events_metrics', 'events.id', '=', 'analytics_events_metrics.event_id')
                    ->orderByDesc('analytics_events_metrics.total_page_views');
                break;
            case 'date':
            default:
                $query->orderBy('start_datetime', 'asc');
        }
    }
}
