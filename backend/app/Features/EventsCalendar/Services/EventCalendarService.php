<?php

namespace App\Features\EventsCalendar\Services;

use App\Models\Event;
use App\Models\EventsCalendarSummary;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EventCalendarService
{
    public function getMonthOverview(array $filters): array
    {
        $monthDate = isset($filters['date'])
            ? Carbon::createFromFormat('Y-m-d', $filters['date'])
            : now();

        $filters['start_date'] = $filters['start_date'] ?? $monthDate->copy()->startOfMonth()->toDateString();
        $filters['end_date'] = $filters['end_date'] ?? $monthDate->copy()->endOfMonth()->toDateString();

        return $this->getRangeOverview($filters);
    }

    public function getDayDetails(array $filters): array
    {
        $day = isset($filters['date'])
            ? Carbon::createFromFormat('Y-m-d', $filters['date'])
            : now();

        $filters['start_date'] = $day->toDateString();
        $filters['end_date'] = $day->toDateString();

        return $this->getRangeOverview($filters);
    }

    public function getRangeOverview(array $filters): array
    {
        $query = $this->buildEventsQuery($filters);
        $sortDirection = ($filters['sort'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = $this->resolvePerPage($filters);

        $events = $query
            ->orderBy('events.start_datetime', $sortDirection)
            ->paginate($perPage)
            ->appends($filters);

        $startDate = $filters['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $filters['end_date'] ?? now()->endOfMonth()->toDateString();

        $summary = EventsCalendarSummary::query()
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

        return [
            'events' => $events,
            'summary' => $summary,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $filters['status'] ?? 'published',
                'category' => $filters['category'] ?? null,
                'min_price' => $filters['min_price'] ?? null,
                'max_price' => $filters['max_price'] ?? null,
                'organizer_id' => $filters['organizer_id'] ?? null,
                'per_page' => $perPage,
                'sort' => $sortDirection,
            ],
        ];
    }

    private function buildEventsQuery(array $filters)
    {
        $inventoryAgg = DB::table('ticket_inventory')
            ->select([
                'event_id',
                DB::raw('SUM(total_available) as total_available_sum'),
                DB::raw('SUM(total_sold) as total_sold_sum'),
            ])
            ->groupBy('event_id');

        $pricingAgg = DB::table('pricing_windows')
            ->select([
                'event_id',
                DB::raw('MIN(price) as min_price'),
                DB::raw('MAX(price) as max_price'),
            ])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->groupBy('event_id');

        $query = Event::query()
            ->leftJoinSub($inventoryAgg, 'inv', function ($join) {
                $join->on('inv.event_id', '=', 'events.id');
            })
            ->leftJoinSub($pricingAgg, 'pw', function ($join) {
                $join->on('pw.event_id', '=', 'events.id');
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
            ]);

        $status = $filters['status'] ?? 'published';
        $query->where('events.status', $status);

        if (!empty($filters['start_date'])) {
            $query->where('events.start_datetime', '>=', $filters['start_date'] . ' 00:00:00');
        }

        if (!empty($filters['end_date'])) {
            $query->where('events.start_datetime', '<=', $filters['end_date'] . ' 23:59:59');
        }

        if (!empty($filters['category'])) {
            $query->where('events.category', $filters['category']);
        }

        if (!empty($filters['organizer_id'])) {
            $query->where('events.organizer_id', (int) $filters['organizer_id']);
        }

        if (isset($filters['min_price'])) {
            $query->where('pw.min_price', '>=', (float) $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('pw.max_price', '<=', (float) $filters['max_price']);
        }

        return $query;
    }

    public function getDateGroupedAvailability(array $filters): array
    {
        $startDate = $filters['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $filters['end_date'] ?? now()->endOfMonth()->toDateString();
        $status = $filters['status'] ?? 'published';

        $query = DB::table('calendar_events_availability')
            ->where('status', $status)
            ->whereBetween('event_date', [$startDate, $endDate]);

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        return $query
            ->orderBy('event_date')
            ->get([
                'event_id',
                'event_date',
                'availability_status',
                'total_tickets',
                'sold_tickets',
                'reserved_tickets',
                'remaining_tickets',
            ])
            ->groupBy('event_date')
            ->toArray();
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
