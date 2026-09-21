<?php

namespace App\Features\EventsCalendar\Services;

use App\Features\EventsCalendar\Http\Resources\CalendarEventResource;
use App\Models\Event;
use App\Models\EventsCalendarSummary;
use App\Models\Organizer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class EventCalendarService
{
    /**
     * Get month overview for the calendar grid.
     * Honors ?timezone= param to correctly map user-local dates to UTC stored events.
     */
    public function getMonthOverview(array $filters): array
    {
        $tz = $this->resolveTimezone($filters['timezone'] ?? null);

        $monthDate = isset($filters['date'])
            ? Carbon::createFromFormat('Y-m-d', $filters['date'], $tz)->setTimezone('UTC')
            : Carbon::now($tz);

        $filters['start_date'] = $filters['start_date'] ?? $monthDate->copy()->startOfMonth()->toDateString();
        $filters['end_date'] = $filters['end_date'] ?? $monthDate->copy()->endOfMonth()->toDateString();

        return $this->getRangeOverview($filters, $tz);
    }

    /**
     * Get day detail. The :date route param is in user's local time (or UTC if no tz).
     */
    public function getDayDetails(array $filters): array
    {
        $tz = $this->resolveTimezone($filters['timezone'] ?? null);

        $day = isset($filters['date'])
            ? Carbon::createFromFormat('Y-m-d', $filters['date'], $tz)->setTimezone('UTC')
            : Carbon::now($tz);

        $filters['start_date'] = $day->toDateString();
        $filters['end_date'] = $day->toDateString();

        return $this->getRangeOverview($filters, $tz);
    }

    /**
     * Get range overview. Both start_date and end_date are user-local; converted to UTC.
     */
    public function getRangeOverview(array $filters, ?string $userTimezone = null): array
    {
        $tz = $userTimezone ?? $this->resolveTimezone($filters['timezone'] ?? null);
        $query = $this->buildEventsQuery($filters, $tz);

        $sortDirection = ($filters['sort'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $sortBy = $filters['sort_by'] ?? 'date';
        $perPage = $this->resolvePerPage($filters);

        $events = $query
            ->with('organizer')
            ->orderBy($this->resolveSortColumn($sortBy), $sortDirection)
            ->paginate($perPage)
            ->appends($filters);

        // Wrap in Resource to strip sensitive organizer data
        $events->setCollection(
            $events->getCollection()->map(fn ($event) => new CalendarEventResource($event))
        );

        $startDate = $filters['start_date'] ?? Carbon::now($tz)->startOfMonth()->toDateString();
        $endDate = $filters['end_date'] ?? Carbon::now($tz)->endOfMonth()->toDateString();

        // Cache key MUST include all filters that affect the result (P0 fix)
        $cacheKey = $this->buildSummaryCacheKey($filters, $startDate, $endDate);
        $summary = Cache::remember($cacheKey, 300, function () use ($startDate, $endDate) {
            return EventsCalendarSummary::query()
                ->inDateRange($startDate, $endDate)
                ->orderBy('event_date')
                ->get([
                    'event_date',
                    'total_events',
                    'published_events',
                    'published_capacity',
                    'draft_events',
                    'cancelled_events',
                    'last_refreshed_at',
                ]);
        });

        return [
            'events' => $events,
            'summary' => $summary,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'published',
                'category' => $filters['category'] ?? null,
                'location' => $filters['location'] ?? null,
                'min_price' => $filters['min_price'] ?? null,
                'max_price' => $filters['max_price'] ?? null,
                'organizer_id' => $filters['organizer_id'] ?? null,
                'per_page' => $perPage,
                'sort_by' => $sortBy,
                'sort' => $sortDirection,
                'timezone' => $tz,
            ],
        ];
    }

    /**
     * Build the events query with timezone-aware date filtering and optional organizer public check.
     */
    private function buildEventsQuery(array $filters, string $userTimezone = 'UTC')
    {
        $inventoryAgg = DB::table('ticket_inventory')
            ->select([
                'event_id',
                DB::raw('SUM(total_allocated - total_sold) as total_available_sum'),
                DB::raw('SUM(total_sold) as total_sold_sum'),
            ])
            ->groupBy('event_id');

        $pricingAgg = DB::table('pricing_windows')
            ->select([
                'event_id',
                DB::raw('MIN(CAST(price AS REAL)) as min_price'),
                DB::raw('MAX(CAST(price AS REAL)) as max_price'),
            ])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->groupBy('event_id');

        $popularityAgg = DB::table('tickets')
            ->select([
                'event_id',
                DB::raw('COUNT(*) as tickets_sold'),
            ])
            ->groupBy('event_id');

        $query = Event::query()
            ->with('organizer')
            ->leftJoinSub($inventoryAgg, 'inv', function ($join) {
                $join->on('inv.event_id', '=', 'events.id');
            })
            ->leftJoinSub($pricingAgg, 'pw', function ($join) {
                $join->on('pw.event_id', '=', 'events.id');
            })
            ->leftJoinSub($popularityAgg, 'pop', function ($join) {
                $join->on('pop.event_id', '=', 'events.id');
            })
            ->select([
                'events.id',
                'events.organizer_id',
                'events.title',
                'events.start_datetime',
                'events.end_datetime',
                'events.venue_name',
                'events.venue_address',
                'events.status',
                'events.category',
                'events.capacity',
                DB::raw('COALESCE(inv.total_available_sum, 0) as total_available'),
                DB::raw('COALESCE(inv.total_sold_sum, 0) as total_sold'),
                DB::raw('pw.min_price as min_price'),
                DB::raw('pw.max_price as max_price'),
                DB::raw('COALESCE(pop.tickets_sold, 0) as popularity'),
            ]);

        // Always enforce published-only and public-only — never allow user to override
        $query->where('events.status', 'published');
        $query->where('events.is_public', true);

        // Global organizer public filter: only show events from verified public organizers.
        // This prevents leaking events from private or unverified organizers regardless of
        // whether the organizer_id query param is used (defense-in-depth alongside the
        // organizer_id-specific check below).
        $query->whereExists(function ($q) {
            $q->selectRaw('1')
              ->from('organizers')
              ->whereRaw('organizers.id = events.organizer_id')
              ->where('organizers.isPublic', true)
              ->where('organizers.verificationStatus', 'verified');
        });

        // Timezone-aware date filtering: convert user-local dates to UTC for DB comparison.
        // Uses interval overlap test: event [start, end] overlaps query [utcStart, utcEnd]
        // iff end >= utcStart AND start <= utcEnd. This correctly includes events that
        // span across the queried date range (e.g., started yesterday, ends tomorrow).
        if (!empty($filters['start_date'])) {
            $utcStart = Carbon::createFromFormat('Y-m-d', $filters['start_date'], $userTimezone)
                ->startOfDay()
                ->setTimezone('UTC')
                ->toDateTimeString();
            $query->where('events.end_datetime', '>=', $utcStart);
        }

        if (!empty($filters['end_date'])) {
            $utcEnd = Carbon::createFromFormat('Y-m-d', $filters['end_date'], $userTimezone)
                ->endOfDay()
                ->setTimezone('UTC')
                ->toDateTimeString();
            $query->where('events.start_datetime', '<=', $utcEnd);
        }

        if (!empty($filters['category'])) {
            $query->where('events.category', $filters['category']);
        }

        if (!empty($filters['location'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('events.venue_name', 'like', '%' . $filters['location'] . '%')
                  ->orWhere('events.venue_address', 'like', '%' . $filters['location'] . '%');
            });
        }

        // Organizer filter: only allow if organizer profile is public (P1 fix)
        if (!empty($filters['organizer_id'])) {
            $organizerId = (int) $filters['organizer_id'];

            $organizer = Organizer::query()
                ->where('id', $organizerId)
                ->where('isPublic', true)
                ->where('verificationStatus', 'verified')
                ->first();

            if (!$organizer) {
                // Force no results — don't leak that the organizer exists
                $query->whereRaw('1 = 0');
            } else {
                $query->where('events.organizer_id', $organizerId);
            }
        }

        if (isset($filters['min_price'])) {
            $query->whereRaw('CAST(pw.min_price AS REAL) >= ?', [(float) $filters['min_price']]);
        }

        if (isset($filters['max_price'])) {
            $query->whereRaw('CAST(pw.max_price AS REAL) <= ?', [(float) $filters['max_price']]);
        }

        return $query;
    }

    private function resolveSortColumn(string $sortBy): string
    {
        return match ($sortBy) {
            'price' => 'pw.min_price',
            'popularity' => 'popularity',
            default => 'events.start_datetime',
        };
    }

    /**
     * Resolve timezone from input or default to UTC.
     * Uses Laravel's timezone validation (DateTimeZone::listIdentifiers()).
     */
    private function resolveTimezone(?string $tz): string
    {
        if ($tz && in_array($tz, \DateTimeZone::listIdentifiers())) {
            return $tz;
        }
        return 'UTC';
    }

    /**
     * Build cache key that includes ALL filters affecting the summary result.
     * Uses a hash of the validated filter set to avoid key collisions.
     */
    private function buildSummaryCacheKey(array $filters, string $startDate, string $endDate): string
    {
        // Only include filters that affect the summary query
        $summaryRelevant = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'category' => $filters['category'] ?? null,
            'location' => $filters['location'] ?? null,
            'organizer_id' => $filters['organizer_id'] ?? null,
        ];

        $hash = md5(serialize($summaryRelevant));
        return "calendar_summary_{$hash}";
    }

    private function resolvePerPage(array $filters): int
    {
        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 50;

        if ($perPage < 1) {
            return 1;
        }

        if ($perPage > 200) {
            return 200;
        }

        return $perPage;
    }
}
