<?php

namespace App\Features\EventsCalendar\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * Raw-query helper for calendar availability lookups.
 *
 * Sits alongside (and can replace parts of) EventCalendarService.
 * Exposes a fluent, Eloquent-like API that either reads from the
 * `calendar_event_availability_view` database view (when migrations
 * are applied) OR falls back to an equivalent in-code subquery so
 * the helper works on any connection — including SQLites used in
 * unit tests that may not have run the view migration.
 */
class CalendarAvailabilityQuery
{
    /** @var array<string, mixed> */
    private array $filters = [];

    /** @var bool — when TRUE, always use the in-code subquery. */
    private bool $forceRaw = false;

    public static function new(): self
    {
        return new self();
    }

    // ------------------------------------------------------------------
    // Fluent filter setters
    // ------------------------------------------------------------------
    public function status(string $status): self
    {
        $this->filters['status'] = $status;
        return $this;
    }

    public function category(string $category): self
    {
        $this->filters['category'] = $category;
        return $this;
    }

    public function organizer(int $organizerId): self
    {
        $this->filters['organizer_id'] = $organizerId;
        return $this;
    }

    public function startDate(string $date): self
    {
        $this->filters['start_date'] = $date;
        return $this;
    }

    public function endDate(string $date): self
    {
        $this->filters['end_date'] = $date;
        return $this;
    }

    public function monthOf(int $year, int $month): self
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();
        return $this->startDate($start->toDateString())
                    ->endDate($end->toDateString());
    }

    public function priceBetween(float $min, float $max): self
    {
        $this->filters['min_price'] = $min;
        $this->filters['max_price'] = $max;
        return $this;
    }

    public function forceRaw(bool $flag = true): self
    {
        $this->forceRaw = $flag;
        return $this;
    }

    // ------------------------------------------------------------------
    // Executors
    // ------------------------------------------------------------------

    /**
     * Fetch per-date aggregate rows (one per date).
     * Ideal for the month-view grid "dot" indicators.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getDateSummary()
    {
        $view = 'calendar_date_availability_summary_view';

        if ($this->forceRaw || !$this->viewExists($view)) {
            $base = $this->rawDateSummarySubquery();
        } else {
            $base = DB::table($view);
        }

        $base = $this->applyDateFilters($base, 'event_date');

        return $base->orderBy('event_date')->get();
    }

    /**
     * Fetch per-event rows, one per (date × event).
     * Includes availability status, min/max price, sell-through %.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getEventRows()
    {
        $view = 'calendar_event_availability_view';

        if ($this->forceRaw || !$this->viewExists($view)) {
            $base = $this->rawEventAvailabilitySubquery();
        } else {
            $base = DB::table($view);
        }

        $base = $this->applyDateFilters($base, 'event_date');
        $base = $this->applyEventFilters($base);

        return $base->orderBy('start_datetime')->get();
    }

    /**
     * Like getEventRows() but returns a Laravel paginator for list views.
     */
    public function paginateEventRows(int $perPage = 50)
    {
        $view = 'calendar_event_availability_view';

        if ($this->forceRaw || !$this->viewExists($view)) {
            $base = $this->rawEventAvailabilitySubquery();
        } else {
            $base = DB::table($view);
        }

        $base = $this->applyDateFilters($base, 'event_date');
        $base = $this->applyEventFilters($base);

        return $base->orderBy('start_datetime')->paginate($perPage);
    }

    // ------------------------------------------------------------------
    // Internal helpers
    // ------------------------------------------------------------------

    private function viewExists(string $name): bool
    {
        try {
            $schema = DB::getDoctrineSchemaManager();
            return $schema->tablesExist([$name]) || $schema->viewsExist([$name]);
        } catch (\Throwable $e) {
            // Doctrine may not be installed or the view can't be introspected.
            // Fall back to an existence probe: attempt a SELECT LIMIT 0.
            try {
                DB::table($name)->limit(0)->get();
                return true;
            } catch (\Throwable $e2) {
                return false;
            }
        }
    }

    private function applyDateFilters($builder, string $column)
    {
        if (!empty($this->filters['start_date'])) {
            $builder->where($column, '>=', $this->filters['start_date']);
        }
        if (!empty($this->filters['end_date'])) {
            $builder->where($column, '<=', $this->filters['end_date']);
        }
        return $builder;
    }

    private function applyEventFilters($builder)
    {
        if (!empty($this->filters['status'])) {
            $builder->where('status', $this->filters['status']);
        }
        if (!empty($this->filters['category'])) {
            $builder->where('category', $this->filters['category']);
        }
        if (!empty($this->filters['organizer_id'])) {
            $builder->where('organizer_id', (int) $this->filters['organizer_id']);
        }
        if (isset($this->filters['min_price'])) {
            $builder->where('min_price', '>=', (float) $this->filters['min_price']);
        }
        if (isset($this->filters['max_price'])) {
            $builder->where('max_price', '<=', (float) $this->filters['max_price']);
        }
        return $builder;
    }

    /**
     * In-code equivalent of `calendar_event_availability_view`.
     * Used when the DB view isn't available.
     */
    private function rawEventAvailabilitySubquery()
    {
        $driver = DB::getDriverName();

        $inventoryAgg = DB::table('ticket_inventory')
            ->select([
                'event_id',
                DB::raw('COALESCE(SUM(total_allocated), 0) AS inv_allocated'),
                DB::raw('COALESCE(SUM(total_sold), 0)      AS inv_sold'),
                DB::raw('COALESCE(SUM(low_stock_threshold), 0) AS inv_low_threshold'),
            ])
            ->groupBy('event_id');

        $pricingAgg = DB::table('pricing_windows')
            ->select([
                'event_id',
                DB::raw('MIN(price) AS pw_min_price'),
                DB::raw('MAX(price) AS pw_max_price'),
            ])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->groupBy('event_id');

        $dateFn = $driver === 'sqlite' ? "DATE(start_datetime)" : "DATE(start_datetime)";

        return DB::table('events AS e')
            ->leftJoinSub($inventoryAgg, 'ti', 'ti.event_id', '=', 'e.id')
            ->leftJoinSub($pricingAgg,   'pw', 'pw.event_id', '=', 'e.id')
            ->select([
                DB::raw("{$dateFn} AS event_date"),
                'e.id AS event_id',
                'e.organizer_id',
                'e.title',
                'e.status',
                'e.category',
                'e.capacity AS event_capacity',
                'e.start_datetime',
                'e.end_datetime',
                DB::raw('COALESCE(ti.inv_allocated, 0)                                     AS total_allocated_sum'),
                DB::raw('COALESCE(ti.inv_sold, 0)                                          AS total_sold_sum'),
                DB::raw('COALESCE(ti.inv_allocated, 0) - COALESCE(ti.inv_sold, 0)           AS total_remaining_sum'),
                DB::raw('CASE
                            WHEN COALESCE(ti.inv_allocated, 0) = 0 THEN 0
                            ELSE (COALESCE(ti.inv_sold, 0) * 100.0) / ti.inv_allocated
                         END                                                               AS sell_through_pct'),
                DB::raw('COALESCE(pw.pw_min_price, 0)                                      AS min_price'),
                DB::raw('COALESCE(pw.pw_max_price, 0)                                      AS max_price'),
                DB::raw('CASE
                    WHEN COALESCE(ti.inv_allocated, 0) - COALESCE(ti.inv_sold, 0) = 0
                         AND COALESCE(ti.inv_allocated, 0) > 0
                        THEN 1
                    WHEN COALESCE(ti.inv_allocated, 0) - COALESCE(ti.inv_sold, 0)
                         <= COALESCE(ti.inv_low_threshold, 0)
                        THEN 2
                    WHEN COALESCE(ti.inv_allocated, 0) = 0
                        THEN 3
                    ELSE 0
                 END                                                                      AS availability_status'),
            ])
            ->whereNotNull('e.start_datetime')
            ->whereNull('e.deleted_at');
    }

    /**
     * In-code equivalent of `calendar_date_availability_summary_view`.
     */
    private function rawDateSummarySubquery()
    {
        $driver = DB::getDriverName();
        $dateFn = $driver === 'sqlite' ? "DATE(start_datetime)" : "DATE(start_datetime)";

        $invAvail = DB::table('ticket_inventory')
            ->select([
                'event_id',
                DB::raw('SUM(total_allocated - total_sold) AS total_remaining'),
            ])
            ->groupBy('event_id');

        $invAlloc = DB::table('ticket_inventory')
            ->select([
                'event_id',
                DB::raw('SUM(total_allocated) AS total_allocated'),
            ])
            ->groupBy('event_id');

        return DB::table('events AS e')
            ->leftJoinSub($invAvail, 'ia', 'ia.event_id', '=', 'e.id')
            ->leftJoinSub($invAlloc, 'ia2', 'ia2.event_id', '=', 'e.id')
            ->select([
                DB::raw("{$dateFn} AS event_date"),
                DB::raw('COUNT(*)                                                         AS total_events'),
                DB::raw("SUM(CASE WHEN e.status = 'published' THEN 1 ELSE 0 END)         AS published_events"),
                DB::raw("SUM(CASE WHEN e.status = 'draft'     THEN 1 ELSE 0 END)         AS draft_events"),
                DB::raw("SUM(CASE WHEN e.status = 'cancelled' THEN 1 ELSE 0 END)        AS cancelled_events"),
                DB::raw("COALESCE(SUM(CASE WHEN e.status = 'published'
                                THEN e.capacity ELSE 0 END), 0)                         AS total_capacity"),
                DB::raw("SUM(CASE
                            WHEN e.status = 'published'
                             AND COALESCE(ia.total_remaining, 0) = 0
                             AND COALESCE(ia2.total_allocated, 0) > 0
                            THEN 1 ELSE 0
                         END)                                                            AS sold_out_events"),
                DB::raw("SUM(CASE
                            WHEN e.status = 'published'
                             AND COALESCE(ia.total_remaining, 0) > 0
                            THEN 1 ELSE 0
                         END)                                                            AS available_events"),
            ])
            ->whereNotNull('e.start_datetime')
            ->whereNull('e.deleted_at')
            ->groupBy(DB::raw($dateFn));
    }
}
