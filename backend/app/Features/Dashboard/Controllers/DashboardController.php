<?php

namespace App\Features\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Features\Dashboard\Models\OrganizerDashboardPreferences;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\AnalyticsEventsMetric;
use App\Models\AnalyticsSalesTimeline;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    private function getOrganizerForUser($user)
    {
        return Organizer::where('user_id', $user->id)->first();
    }

    private function authorizeDashboardAccess(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if ($user->hasRole('admin')) {
            return;
        }

        $organizer = $this->getOrganizerForUser($user);
        if (!$organizer) {
            abort(403, 'You do not have access to this dashboard.');
        }
    }

    /**
     * Build the base query for an organizer's non-deleted events.
     */
    private function getEventQueryForOrganizer(Request $request)
    {
        $user = $request->user();
        $query = Event::query()->whereNull('deleted_at');

        if (!$user->hasRole('admin')) {
            $organizer = $this->getOrganizerForUser($user);
            if ($organizer) {
                $query->where('organizer_id', $organizer->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }

    /**
     * GET /api/organizer/dashboard/overview
     * Aggregated metrics with trends, filtered by date range and event.
     */
    public function overview(Request $request)
    {
        $this->authorizeDashboardAccess($request);
        $user = $request->user();

        $validated = $request->validate([
            'dateRange' => 'nullable|in:last_7_days,last_30_days,last_90_days,all_time',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date|after_or_equal:startDate',
            'eventFilter' => 'nullable|string',
        ]);

        $dateRange = $validated['dateRange'] ?? 'last_30_days';
        $eventFilter = $validated['eventFilter'] ?? null;

        $cacheKey = 'dashboard.overview.' . $user->id . '.' . $dateRange . '.' . ($eventFilter ?? 'all');

        return Cache::remember($cacheKey, 180, function () use ($request, $dateRange, $validated, $eventFilter) {
            [$startDate, $endDate] = $this->resolveDateRange($dateRange, $validated);

            // Get event IDs — single pluck query
            $eventQuery = $this->getEventQueryForOrganizer($request);
            if ($eventFilter && $eventFilter !== 'all') {
                $eventQuery->where('id', $eventFilter);
            }
            $eventIds = $eventQuery->pluck('id')->toArray();
            $totalEvents = count($eventIds);

            if ($totalEvents === 0) {
                return response()->json([
                    'success' => true,
                    'metrics' => $this->emptyMetrics(),
                    'trends' => $this->emptyTrends(),
                ]);
            }

            // Single aggregated query for current period
            $current = AnalyticsEventsMetric::whereIn('event_id', $eventIds)
                ->selectRaw('COALESCE(SUM(total_tickets_sold), 0) as tickets_sold, COALESCE(SUM(total_revenue), 0) as revenue, COALESCE(SUM(total_page_views), 0) as page_views')
                ->first();

            // Previous period for trends
            $periodLength = $startDate->diffInSeconds($endDate);
            $prevStart = (clone $startDate)->subSeconds($periodLength);
            $prevEnd = (clone $endDate)->subSeconds($periodLength);

            $previous = AnalyticsEventsMetric::whereIn('event_id', $eventIds)
                ->whereBetween('last_updated_at', [$prevStart, $prevEnd])
                ->selectRaw('COALESCE(SUM(total_tickets_sold), 0) as tickets_sold, COALESCE(SUM(total_revenue), 0) as revenue, COALESCE(SUM(total_page_views), 0) as page_views')
                ->first();

            $eventsPublished = Event::whereIn('id', $eventIds)->where('status', 'published')->count();

            return response()->json([
                'success' => true,
                'metrics' => [
                    'totalEvents' => $totalEvents,
                    'eventsPublished' => $eventsPublished,
                    'totalTicketsSold' => (int) ($current->tickets_sold ?? 0),
                    'totalRevenue' => (float) ($current->revenue ?? 0),
                    'totalPageViews' => (int) ($current->page_views ?? 0),
                ],
                'trends' => [
                    'ticketsSold' => $this->calculateTrend((float) ($current->tickets_sold ?? 0), (float) ($previous->tickets_sold ?? 0)),
                    'revenue' => $this->calculateTrend((float) ($current->revenue ?? 0), (float) ($previous->revenue ?? 0)),
                    'pageViews' => $this->calculateTrend((float) ($current->page_views ?? 0), (float) ($previous->page_views ?? 0)),
                ],
            ]);
        });
    }

    /**
     * GET /api/organizer/dashboard/metrics — legacy alias.
     */
    public function getMetrics(Request $request)
    {
        return $this->overview($request);
    }

    /**
     * GET /api/organizer/dashboard/events
     * Paginated event list with sorting.
     */
    public function events(Request $request)
    {
        $this->authorizeDashboardAccess($request);

        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1|max:50',
            'sortBy' => 'nullable|in:date,revenue,tickets,title,status',
            'sortOrder' => 'nullable|in:asc,desc',
            'status' => 'nullable|in:published,draft,cancelled,flagged,all',
        ]);

        $perPage = $validated['perPage'] ?? 10;
        $sortBy = $validated['sortBy'] ?? 'date';
        $sortOrder = $validated['sortOrder'] ?? 'desc';
        $status = $validated['status'] ?? 'all';

        $query = $this->getEventQueryForOrganizer($request)->with('analyticsEventsMetric');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $sortColumn = match ($sortBy) {
            'date' => 'start_datetime',
            'title' => 'title',
            'status' => 'status',
            default => 'created_at',
        };

        $query->orderBy($sortColumn, $sortOrder);

        $events = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'events' => $events->map(fn ($event) => [
                'id' => $event->id,
                'title' => $event->title,
                'status' => $event->status,
                'startDatetime' => $event->start_datetime?->toIso8601String(),
                'endDatetime' => $event->end_datetime?->toIso8601String(),
                'venueName' => $event->venue_name,
                'capacity' => $event->capacity,
                'ticketsSold' => $event->analyticsEventsMetric ? (int) $event->analyticsEventsMetric->total_tickets_sold : 0,
                'revenue' => $event->analyticsEventsMetric ? (float) $event->analyticsEventsMetric->total_revenue : 0.0,
            ]),
            'pagination' => [
                'currentPage' => $events->currentPage(),
                'lastPage' => $events->lastPage(),
                'perPage' => $events->perPage(),
                'total' => $events->total(),
            ],
        ]);
    }

    /**
     * GET /api/organizer/dashboard/events/:eventId
     * Detailed event metrics with tier breakdown.
     */
    public function eventDetail(Request $request, $eventId)
    {
        $this->authorizeDashboardAccess($request);

        $event = $this->getEventQueryForOrganizer($request)->with(['analyticsEventsMetric', 'ticketTiers' => fn ($q) => $q->withTrashed()])->find($eventId);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $metric = $event->analyticsEventsMetric;

        return response()->json([
            'success' => true,
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'status' => $event->status,
                'startDatetime' => $event->start_datetime?->toIso8601String(),
                'endDatetime' => $event->end_datetime?->toIso8601String(),
                'capacity' => $event->capacity,
                'tiers' => $event->ticketTiers->map(fn ($tier) => [
                    'id' => $tier->id,
                    'name' => $tier->name,
                    'price' => (float) $tier->price,
                    'quantity' => $tier->quantity,
                    'soldCount' => (int) ($tier->sold_count ?? 0),
                    'isActive' => (bool) $tier->is_active,
                ]),
                'metrics' => [
                    'totalRevenue' => $metric ? (float) $metric->total_revenue : 0,
                    'totalTicketsSold' => $metric ? (int) $metric->total_tickets_sold : 0,
                    'pageViews' => $metric ? (int) $metric->total_page_views : 0,
                    'conversionRate' => $metric ? (float) $metric->conversion_rate : 0,
                    'averageTicketPrice' => $metric ? (float) $metric->average_ticket_price : 0,
                ],
            ],
        ]);
    }

    /**
     * GET /api/organizer/dashboard/activity-feed
     * Sales activity sorted by timestamp with filtering.
     */
    public function getActivityFeed(Request $request)
    {
        $this->authorizeDashboardAccess($request);
        $user = $request->user();

        if ($user->hasRole('admin')) {
            return response()->json(['success' => true, 'enabled' => true, 'activities' => []]);
        }

        $organizer = $this->getOrganizerForUser($user);
        if (!$organizer) {
            abort(403, 'No organizer profile found.');
        }

        $prefs = OrganizerDashboardPreferences::where('organizer_id', $organizer->id)->first();
        if (!$prefs || !$prefs->show_activity_feed) {
            return response()->json(['success' => true, 'enabled' => false, 'activities' => []]);
        }

        $validated = $request->validate([
            'eventId' => 'nullable|string',
            'tierId' => 'nullable|string',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date|after_or_equal:startDate',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $limit = $validated['limit'] ?? 20;

        // JOIN instead of subquery for performance
        $query = AnalyticsSalesTimeline::query()
            ->join('events', 'analytics_sales_timeline.event_id', '=', 'events.id')
            ->where('events.organizer_id', $organizer->id)
            ->whereNull('events.deleted_at');

        if (!empty($validated['eventId'])) {
            $query->where('analytics_sales_timeline.event_id', $validated['eventId']);
        }
        if (!empty($validated['tierId'])) {
            $query->where('analytics_sales_timeline.ticket_tier_id', $validated['tierId']);
        }
        if (!empty($validated['startDate'])) {
            $query->where('analytics_sales_timeline.sale_timestamp', '>=', $validated['startDate']);
        }
        if (!empty($validated['endDate'])) {
            $query->where('analytics_sales_timeline.sale_timestamp', '<=', $validated['endDate']);
        }

        $activities = $query->orderBy('analytics_sales_timeline.sale_timestamp', 'desc')
            ->limit($limit)
            ->get(['analytics_sales_timeline.*'])
            ->map(fn ($sale) => [
                'id' => $sale->id,
                'type' => 'ticket_sale',
                'message' => "Ticket sale: {$sale->quantity} ticket(s) for \${$sale->total_amount}",
                'timestamp' => $sale->sale_timestamp?->toIso8601String(),
                'tierId' => $sale->ticket_tier_id,
            ]);

        return response()->json(['success' => true, 'enabled' => true, 'activities' => $activities]);
    }

    /**
     * GET /api/organizer/dashboard/preferences
     */
    public function getPreferences(Request $request)
    {
        $this->authorizeDashboardAccess($request);
        $user = $request->user();

        if ($user->hasRole('admin')) {
            return response()->json(['success' => true, 'preferences' => $this->getDefaultPreferences()]);
        }

        $organizer = $this->getOrganizerForUser($user);
        if (!$organizer) {
            abort(403, 'No organizer profile found.');
        }

        $prefs = OrganizerDashboardPreferences::where('organizer_id', $organizer->id)->first();

        return response()->json([
            'success' => true,
            'preferences' => $prefs ? $this->formatPreferences($prefs) : $this->getDefaultPreferences(),
        ]);
    }

    /**
     * PUT/PATCH /api/organizer/dashboard/preferences
     */
    public function updatePreferences(Request $request)
    {
        $this->authorizeDashboardAccess($request);
        $user = $request->user();

        $validated = $request->validate([
            'default_event_filter' => 'nullable|in:all,upcoming,past,published,draft',
            'default_date_range' => 'nullable|in:last_7_days,last_30_days,last_90_days,all_time',
            'show_activity_feed' => 'nullable|boolean',
            'auto_refresh_enabled' => 'nullable|boolean',
        ]);

        if ($user->hasRole('admin')) {
            return response()->json(['success' => true, 'preferences' => $this->getDefaultPreferences()]);
        }

        $organizer = $this->getOrganizerForUser($user);
        if (!$organizer) {
            abort(403, 'No organizer profile found.');
        }

        $prefs = OrganizerDashboardPreferences::updateOrCreate(
            ['organizer_id' => $organizer->id],
            $validated
        );

        return response()->json(['success' => true, 'preferences' => $this->formatPreferences($prefs)]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    private function resolveDateRange(string $range, array $validated): array
    {
        if (!empty($validated['startDate']) && !empty($validated['endDate'])) {
            return [
                Carbon::parse($validated['startDate'])->startOfDay(),
                Carbon::parse($validated['endDate'])->endOfDay(),
            ];
        }

        $end = Carbon::now();
        $start = match ($range) {
            'last_7_days'  => (clone $end)->subDays(7),
            'last_30_days' => (clone $end)->subDays(30),
            'last_90_days' => (clone $end)->subDays(90),
            default        => Carbon::createFromTimestamp(0),
        };

        return [$start, $end];
    }

    private function calculateTrend(float $current, float $previous): array
    {
        $delta = $current - $previous;
        $pct = $previous > 0 ? round(($delta / $previous) * 100, 2) : ($current > 0 ? 100.0 : 0.0);
        return [
            'direction' => $pct > 0.01 ? 'up' : ($pct < -0.01 ? 'down' : 'flat'),
            'percentageChange' => $pct,
            'delta' => round($delta, 2),
        ];
    }

    private function emptyMetrics(): array
    {
        return ['totalEvents' => 0, 'eventsPublished' => 0, 'totalTicketsSold' => 0, 'totalRevenue' => 0.0, 'totalPageViews' => 0];
    }

    private function emptyTrends(): array
    {
        return [
            'ticketsSold' => ['direction' => 'flat', 'percentageChange' => 0, 'delta' => 0],
            'revenue'     => ['direction' => 'flat', 'percentageChange' => 0, 'delta' => 0],
            'pageViews'   => ['direction' => 'flat', 'percentageChange' => 0, 'delta' => 0],
        ];
    }

    private function getDefaultPreferences(): array
    {
        return [
            'defaultEventFilter' => 'all',
            'defaultDateRange'   => 'last_30_days',
            'showActivityFeed'   => true,
            'autoRefreshEnabled' => false,
        ];
    }

    private function formatPreferences(OrganizerDashboardPreferences $prefs): array
    {
        return [
            'defaultEventFilter' => $prefs->default_event_filter,
            'defaultDateRange'   => $prefs->default_date_range,
            'showActivityFeed'   => (bool) $prefs->show_activity_feed,
            'autoRefreshEnabled' => (bool) $prefs->auto_refresh_enabled,
        ];
    }
}
