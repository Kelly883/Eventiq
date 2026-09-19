<?php

namespace App\Features\Analytics\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AnalyticsEventsMetric;
use App\Models\AnalyticsSalesTimeline;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Maximum allowed date range in days.
     * Prevents unbounded queries over years of data.
     */
    private const MAX_DATE_RANGE_DAYS = 365;

    private function authorizeEventAccess(Request $request, $eventId): void
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        $event = Event::find($eventId);
        if (!$event) {
            abort(404, 'Event not found');
        }

        try {
            Gate::forUser($user)->authorize('view', $event);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, 'You are not authorized to view this event\'s analytics.');
        }
    }

    /**
     * GET /api/organizer/events/{event}/analytics/summary
     * Returns totalRevenue, ticketsSold, conversionRate, averageTicketPrice,
     * totalPageViews, capacity, plus trend indicators vs previous period.
     *
     * Optional query:
     *   - startDate, endDate (ISO 8601) — custom date range
     *   - refresh=true — bypass cache
     */
    public function getSummary(Request $request, $eventId)
    {
        $this->authorizeEventAccess($request, $eventId);

        $user = $request->user();
        $refresh = $request->query('refresh', false);

        // Get organizer's timezone (default to UTC)
        $organizer = $user->organizer ?? \App\Models\Organizer::where('user_id', $user->id)->first();
        $timezone = $organizer?->timezone ?? 'UTC';
        $requestedTimezone = $request->query('timezone', $timezone);

        // Validate timezone
        if (!in_array($requestedTimezone, \DateTimeZone::listIdentifiers(), true)) {
            $requestedTimezone = 'UTC';
        }

        // Parse date range in the requested timezone, then convert to UTC for DB queries
        $endDate = $request->query('endDate')
            ? Carbon::parse($this->normalizeDateInput($request->query('endDate')), $requestedTimezone)->setTimezone('UTC')
            : Carbon::now($requestedTimezone)->setTimezone('UTC');
        $startDate = $request->query('startDate')
            ? Carbon::parse($this->normalizeDateInput($request->query('startDate')), $requestedTimezone)->setTimezone('UTC')
            : (clone $endDate)->subDays(30);

        // Validate range
        if ($startDate->gte($endDate)) {
            return response()->json(['message' => 'startDate must be before endDate'], 400);
        }

        // Cap date range to MAX_DATE_RANGE_DAYS
        $maxStartDate = (clone $endDate)->subDays(self::MAX_DATE_RANGE_DAYS);
        if ($startDate->lt($maxStartDate)) {
            $startDate = $maxStartDate;
        }

        // Cache key includes event, user, and date range so different ranges aren't mixed
        // TTL-based expiration only — no version invalidation
        // This means new sales may not appear for up to TTL seconds, but avoids stale cache issues
        $cacheKey = "analytics:summary:{$eventId}:{$user->id}:" . $startDate->format('Ymd') . ':' . $endDate->format('Ymd');

        if (!$refresh) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return response()->json($cached);
            }
        }

        // Current period metrics
        $current = $this->computeMetrics($eventId, $startDate, $endDate);

        // Previous period of equal length for trend comparison
        $periodLength = $startDate->diffInSeconds($endDate);
        $prevStart = (clone $startDate)->subSeconds($periodLength);
        $prevEnd = (clone $endDate)->subSeconds($periodLength);
        $previous = $this->computeMetrics($eventId, $prevStart, $prevEnd);

        // Get additional metric data
        $metric = AnalyticsEventsMetric::where('event_id', $eventId)->first();

        $response = [
            'success' => true,
            'eventId' => (int) $eventId,
            'timezone' => $requestedTimezone,
            'dateRange' => [
                'startDate' => $startDate->setTimezone($requestedTimezone)->toIso8601String(),
                'endDate' => $endDate->setTimezone($requestedTimezone)->toIso8601String(),
            ],
            'metrics' => [
                'totalRevenue' => $current['revenue'],
                'ticketsSold' => $current['ticketsSold'],
                'ticketCapacity' => $current['capacity'],
                'conversionRate' => $current['conversionRate'],
                'averageTicketPrice' => $current['averageTicketPrice'],
                'pageViews' => $current['pageViews'],
                'refundedTickets' => $current['refundedTickets'],
                'netRevenue' => $current['revenue'] - $current['refundedAmount'],
                'peakSalesHour' => $metric ? $metric->peak_sales_hour : null,
                'topTicketTierId' => $metric ? $metric->top_ticket_tier_id : null,
            ],
            'trends' => [
                'revenue' => $this->calculateTrend($current['revenue'], $previous['revenue']),
                'ticketsSold' => $this->calculateTrend($current['ticketsSold'], $previous['ticketsSold']),
                'conversionRate' => $this->calculateTrend($current['conversionRate'], $previous['conversionRate']),
                'pageViews' => $this->calculateTrend($current['pageViews'], $previous['pageViews']),
            ],
        ];

        Cache::put($cacheKey, $response, 30);

        return response()->json($response);
    }

    /**
     * Normalize date input to a format Carbon can parse consistently.
     * Handles ISO 8601 with timezone offset that some browsers/clients send.
     */
    private function normalizeDateInput(string $date): string
    {
        if (preg_match('/^(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2})/', $date, $matches)) {
            return $matches[1];
        }
        return $date;
    }

    /**
     * GET /api/organizer/events/{event}/analytics/sales-velocity
     * Time-series sales data grouped by interval (hourly, daily, weekly).
     */
    public function getSalesVelocity(Request $request, $eventId)
    {
        $this->authorizeEventAccess($request, $eventId);

        $interval = $request->query('interval', 'daily');

        if (!in_array($interval, ['hourly', 'daily', 'weekly'], true)) {
            return response()->json(['message' => 'Invalid interval. Use hourly, daily, or weekly.'], 400);
        }

        $hasData = AnalyticsSalesTimeline::where('event_id', $eventId)->exists();

        if (!$hasData) {
            return response()->json([
                'success' => true,
                'interval' => $interval,
                'data' => [],
                'hasData' => false,
                'aggregated_on_server' => true,
            ]);
        }

        $driver = DB::connection()->getDriverName();
        $query = AnalyticsSalesTimeline::where('event_id', $eventId);

        $format = match ($interval) {
            'hourly' => match ($driver) {
                'sqlite' => "%Y-%m-%d %H:00:00",
                'mysql' => "%Y-%m-%d %H:00:00",
                'pgsql' => null,
                default => "%Y-%m-%d %H:00:00",
            },
            'daily' => match ($driver) {
                'sqlite' => "%Y-%m-%d",
                'mysql' => "%Y-%m-%d",
                'pgsql' => null,
                default => "%Y-%m-%d",
            },
            'weekly' => match ($driver) {
                'sqlite' => "%Y-%W",
                'mysql' => "%Y-%u",
                'pgsql' => null,
                default => "%Y-%W",
            },
        };

        if ($driver === 'pgsql') {
            $truncUnit = match ($interval) {
                'hourly' => 'hour',
                'daily' => 'day',
                'weekly' => 'week',
            };
            $query->select(
                DB::raw("date_trunc('{$truncUnit}', sale_timestamp) as time_bucket"),
                DB::raw("SUM(quantity) as ticketsSold"),
                DB::raw("SUM(total_amount) as revenue")
            );
        } elseif ($driver === 'sqlite') {
            $query->select(
                DB::raw("strftime('{$format}', sale_timestamp) as time_bucket"),
                DB::raw("SUM(quantity) as ticketsSold"),
                DB::raw("SUM(total_amount) as revenue")
            );
        } else {
            $query->select(
                DB::raw("DATE_FORMAT(sale_timestamp, '{$format}') as time_bucket"),
                DB::raw("SUM(quantity) as ticketsSold"),
                DB::raw("SUM(total_amount) as revenue")
            );
        }

        $aggregatedData = $query->groupBy('time_bucket')
            ->orderBy('time_bucket', 'asc')
            ->get()
            ->map(function ($row) {
                return [
                    'date' => $row->time_bucket,
                    'ticketsSold' => (int) $row->ticketsSold,
                    'revenue' => (float) $row->revenue,
                ];
            });

        return response()->json([
            'success' => true,
            'interval' => $interval,
            'data' => $aggregatedData,
            'hasData' => true,
            'aggregated_on_server' => true,
        ]);
    }

    /**
     * GET /api/organizer/events/{event}/analytics/detailed
     * Tier breakdown + source breakdown.
     */
    public function getDetailed(Request $request, $eventId)
    {
        $this->authorizeEventAccess($request, $eventId);

        $tierBreakdown = AnalyticsSalesTimeline::where('analytics_sales_timeline.event_id', $eventId)
            ->join('ticket_tiers', 'analytics_sales_timeline.ticket_tier_id', '=', 'ticket_tiers.id')
            ->selectRaw('ticket_tiers.id as tier_id')
            ->selectRaw('ticket_tiers.name as tier_name')
            ->selectRaw('COALESCE(SUM(analytics_sales_timeline.quantity), 0) as tickets_sold')
            ->selectRaw('COALESCE(SUM(analytics_sales_timeline.total_amount), 0) as revenue')
            ->groupBy('ticket_tiers.id', 'ticket_tiers.name')
            ->get();

        $totalTicketsSold = $tierBreakdown->sum('tickets_sold');

        $tierBreakdown = $tierBreakdown->map(function ($tier) use ($totalTicketsSold) {
            return [
                'tierId' => (string) $tier->tier_id,
                'tierName' => $tier->tier_name,
                'ticketsSold' => (int) $tier->tickets_sold,
                'revenue' => (float) $tier->revenue,
                'percentageOfTotal' => $totalTicketsSold > 0 ? round(($tier->tickets_sold / $totalTicketsSold) * 100, 2) : 0,
            ];
        })->values();

        $sourceBreakdown = AnalyticsSalesTimeline::where('event_id', $eventId)
            ->selectRaw('COALESCE(source, \'unknown\') as source')
            ->selectRaw('COALESCE(SUM(quantity), 0) as tickets_sold')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as revenue')
            ->groupBy('source')
            ->get()
            ->map(function ($source) {
                return [
                    'source' => $source->source,
                    'ticketsSold' => (int) $source->tickets_sold,
                    'revenue' => (float) $source->revenue,
                ];
            })->values();

        return response()->json([
            'success' => true,
            'eventId' => (int) $eventId,
            'tierBreakdown' => $tierBreakdown,
            'sourceBreakdown' => $sourceBreakdown,
        ]);
    }

    /**
     * GET /api/organizer/analytics/comparison
     * Cross-event comparison for the authenticated organizer.
     */
    public function getComparison(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        $query = Event::query();

        if (!$user->hasRole('admin')) {
            $organizer = $user->organizer;
            if (!$organizer) {
                $organizer = \App\Models\Organizer::where('user_id', $user->id)->first();
            }
            if ($organizer) {
                $query->where('organizer_id', $organizer->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $events = $query->with('analyticsEventsMetric')->get();

        $comparison = $events->map(function ($event) {
            $metric = $event->analyticsEventsMetric;
            return [
                'eventId' => (string) $event->id,
                'eventName' => $event->title,
                'ticketsSold' => $metric ? (int) $metric->total_tickets_sold : 0,
                'revenue' => $metric ? (float) $metric->total_revenue : 0,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'comparison' => $comparison,
        ]);
    }

    /**
     * Compute metrics for a given date range from the sales timeline.
     */
    private function computeMetrics(int $eventId, Carbon $startDate, Carbon $endDate): array
    {
        $aggregates = DB::table('analytics_sales_timeline')
            ->where('event_id', $eventId)
            ->whereBetween('sale_timestamp', [$startDate, $endDate])
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_revenue')
            ->selectRaw('COALESCE(SUM(quantity), 0) as tickets_sold')
            ->first();

        $revenue = (float) ($aggregates->total_revenue ?? 0);
        $ticketsSold = (int) ($aggregates->tickets_sold ?? 0);
        $averageTicketPrice = $ticketsSold > 0 ? round($revenue / $ticketsSold, 2) : 0;

        $metric = AnalyticsEventsMetric::where('event_id', $eventId)->first();
        $pageViews = $metric && $metric->total_page_views !== null ? (int) $metric->total_page_views : 0;
        $conversionRate = $pageViews > 0 ? round(($ticketsSold / $pageViews) * 100, 2) : 0;

        // Count refunded tickets
        // Note: Tickets don't store refund amounts directly — that's in refund_requests.
        // For net revenue, we'd need a join, but that's expensive. We just count here.
        $refundedTickets = \App\Features\Checkout\Models\Ticket::where('event_id', $eventId)
            ->where('refund_status', 'refunded')
            ->count();

        $refundedAmount = 0; // Would require join with refund_requests for exact amount

        $event = Event::find($eventId);
        $capacity = $event && $event->capacity !== null ? (int) $event->capacity : 0;

        return [
            'revenue' => $revenue,
            'ticketsSold' => $ticketsSold,
            'capacity' => $capacity,
            'conversionRate' => $conversionRate,
            'averageTicketPrice' => $averageTicketPrice,
            'pageViews' => $pageViews,
            'refundedTickets' => $refundedTickets,
            'refundedAmount' => $refundedAmount,
        ];
    }

    /**
     * Calculate trend between current and previous period.
     * Returns array with direction, percentage change, and absolute delta.
     */
    private function calculateTrend(float $current, float $previous): array
    {
        $delta = $current - $previous;
        $percentageChange = $previous > 0
            ? round(($delta / $previous) * 100, 2)
            : ($current > 0 ? 100.0 : 0.0);

        $direction = match (true) {
            $percentageChange > 0.01 => 'up',
            $percentageChange < -0.01 => 'down',
            default => 'flat',
        };

        return [
            'direction' => $direction,
            'percentageChange' => $percentageChange,
            'delta' => round($delta, 2),
        ];
    }
}
