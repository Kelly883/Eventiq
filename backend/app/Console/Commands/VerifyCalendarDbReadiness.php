<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Step 65 verification command: confirms that all calendar query indexes
 * are in place, runs EXPLAIN on the core queries, and benchmarks a few
 * sample calendar lookups (e.g. "published events in March 2024").
 *
 * Usage:
 *   php artisan calendar:verify-db-readiness
 */
class VerifyCalendarDbReadiness extends Command
{
    protected $signature   = 'calendar:verify-db-readiness {--benchmark=5}';
    protected $description = 'Step 65 — verify calendar indexes + benchmark queries.';

    // ---- Expected indexes, grouped by table ---------------------------
    private const EXPECTED_INDEXES = [
        'events' => [
            'idx_events_status_start_datetime',
            'idx_events_status_category',
            'idx_events_start_datetime',
            'idx_events_status',
            'idx_events_organizer_status_date',
            'idx_events_category',
        ],
        'ticket_inventory' => [
            'idx_ticket_inventory_event_tier',
            'idx_ticket_inventory_event_id',
        ],
        'pricing_windows' => [
            'idx_pricing_windows_event_tier',
            'idx_pricing_windows_event_id',
            'idx_pricing_windows_active_event',
        ],
        'events_calendar_summary' => [
            'idx_events_calendar_summary_date',
        ],
    ];

    // ---- Views that should exist after the view migration -------------
    private const EXPECTED_VIEWS = [
        'calendar_event_availability_view',
        'calendar_date_availability_summary_view',
    ];

    public function handle(): int
    {
        $this->output->title('Step 65 — Calendar DB Readiness Verification');

        $ok = true;

        $ok &= $this->checkIndexes();
        $ok &= $this->checkViews();
        $ok &= $this->runExplainPlans();
        $ok &= $this->benchmarkQueries((int) $this->option('benchmark'));

        $this->output->newLine();
        if ($ok) {
            $this->output->success('✅ ALL CHECKS PASSED');
            return self::SUCCESS;
        }

        $this->output->warning('⚠️  Some checks failed — review output above.');
        return self::FAILURE;
    }

    // =================================================================
    // 1. INDEX VERIFICATION
    // =================================================================
    private function checkIndexes(): bool
    {
        $this->output->section('1. Index verification');

        $pass = true;
        $driver = DB::getDriverName();

        foreach (self::EXPECTED_INDEXES as $table => $indexNames) {
            if (!Schema::hasTable($table)) {
                $this->output->note("Table {$table} does not exist — skipping.");
                continue;
            }

            $rows = $this->fetchIndexes($table, $driver);
            $existing = array_values(array_unique(array_column($rows, 'Key_name')));

            foreach ($indexNames as $name) {
                $found = in_array($name, $existing, true);
                if ($found) {
                    $this->output->writeln("  ✅ {$table}.{$name}");
                } else {
                    $this->output->writeln("  ❌ {$table}.{$name}  (MISSING)");
                    $pass = false;
                }
            }

            // Also dump the actual indexes we found so the user can verify by hand.
            $this->output->writeln(
                "     → indexes actually found on {$table}: " . implode(', ', $existing)
            );
        }

        return $pass;
    }

    private function fetchIndexes(string $table, string $driver): array
    {
        try {
            if ($driver === 'mysql') {
                return DB::select("SHOW INDEX FROM `{$table}`");
            }
            if ($driver === 'sqlite') {
                return DB::select("PRAGMA index_list(`{$table}`)");
            }
            if ($driver === 'pgsql') {
                return DB::select("
                    SELECT indexname AS Key_name FROM pg_indexes
                     WHERE tablename = ?
                ", [$table]);
            }
        } catch (\Throwable $e) {
            $this->output->warning("Could not fetch indexes for {$table}: " . $e->getMessage());
        }
        return [];
    }

    // =================================================================
    // 2. VIEW VERIFICATION
    // =================================================================
    private function checkViews(): bool
    {
        $this->output->section('2. Database view verification');

        $pass = true;
        foreach (self::EXPECTED_VIEWS as $view) {
            try {
                DB::table($view)->limit(0)->get();
                $this->output->writeln("  ✅ VIEW {$view} (SELECT succeeded)");
            } catch (\Throwable $e) {
                $this->output->writeln("  ❌ VIEW {$view} (UNAVAILABLE: " . $e->getMessage() . ")");
                $pass = false;
            }
        }
        return $pass;
    }

    // =================================================================
    // 3. EXPLAIN PLANS
    // =================================================================
    private function runExplainPlans(): bool
    {
        $this->output->section('3. EXPLAIN query plans');

        $queries = [
            'Published events in March 2024' => $this->publishedMarch2024Query(),
            'Events filtered by category'    => $this->categoryFilterQuery('concert'),
            'Events with price range'        => $this->priceRangeQuery(20, 100),
            'Calendar join with inventory + pricing (raw helper subqueries)'
                => $this->calendarJoinQuery(),
        ];

        $pass = true;
        foreach ($queries as $label => $sqlAndBindings) {
            $this->output->writeln("  📝 {$label}");
            try {
                $explainRows = DB::select('EXPLAIN ' . $sqlAndBindings[0], $sqlAndBindings[1]);
                // Heuristic: at least one row should reference an index.
                $anyIndexUsed = false;
                foreach ($explainRows as $row) {
                    $rowArr = (array) $row;
                    // MySQL: 'type' column != ALL, or key is not null.
                    if (
                        !empty($rowArr['key'])
                        || (isset($rowArr['type']) && $rowArr['type'] !== 'ALL')
                        || !empty($rowArr['using_index'])
                    ) {
                        $anyIndexUsed = true;
                    }
                }

                if ($anyIndexUsed) {
                    $this->output->writeln("     ✅ Indexes used");
                } else {
                    $this->output->writeln("     ⚠️  No index detected in plan (raw table scan likely)");
                    $pass = false;
                }

                // Emit first EXPLAIN row as a debug hint.
                if (!empty($explainRows[0])) {
                    $first = json_encode($explainRows[0], JSON_UNESCAPED_SLASHES);
                    $this->output->writeln("     → explain sample: " . $first);
                }
            } catch (\Throwable $e) {
                $this->output->writeln("     ❌ EXPLAIN failed: " . $e->getMessage());
                $pass = false;
            }
        }
        return $pass;
    }

    // =================================================================
    // 4. BENCHMARKS  (<100ms target per query)
    // =================================================================
    private function benchmarkQueries(int $iterations): bool
    {
        $this->output->section("4. Benchmarks ({$iterations} iterations each — target: <100ms)");

        $cases = [
            'Published events March 2024'   => $this->publishedMarch2024Query(),
            'Events by category = concert' => $this->categoryFilterQuery('concert'),
            'Events price between 20-100'  => $this->priceRangeQuery(20, 100),
            'Calendar full join (inv+price)' => $this->calendarJoinQuery(),
        ];

        $pass = true;
        foreach ($cases as $label => $qb) {
            $start = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                DB::select($qb[0], $qb[1]);
            }
            $elapsed = (microtime(true) - $start) * 1000; // ms
            $perCall = $elapsed / max(1, $iterations);
            $withinTarget = $perCall < 100;
            $pass &= $withinTarget;
            $status = $withinTarget ? '✅' : '❌ TOO SLOW';
            $this->output->writeln(sprintf(
                '  %s %-45s  avg=%.2f ms  total=%.2f ms',
                $status,
                $label,
                $perCall,
                $elapsed
            ));
        }

        return $pass;
    }

    // =================================================================
    // Query builders — returns [SQL string, bindings array]
    // =================================================================
    private function publishedMarch2024Query(): array
    {
        return [
            "SELECT id, title, status, start_datetime
             FROM events
             WHERE status = ?
               AND start_datetime >= ?
               AND start_datetime <= ?
             ORDER BY start_datetime ASC",
            [
                'published',
                '2024-03-01 00:00:00',
                '2024-03-31 23:59:59',
            ],
        ];
    }

    private function categoryFilterQuery(string $category): array
    {
        return [
            "SELECT id, title, status, category, start_datetime
             FROM events
             WHERE status = ?
               AND category = ?
             ORDER BY start_datetime ASC
             LIMIT 100",
            ['published', $category],
        ];
    }

    private function priceRangeQuery(float $min, float $max): array
    {
        return [
            "SELECT e.id, e.title, MIN(pw.price) min_p, MAX(pw.price) max_p
             FROM events e
             LEFT JOIN pricing_windows pw
                    ON pw.event_id = e.id
                   AND pw.is_active = 1
                   AND pw.deleted_at IS NULL
             WHERE e.status = 'published'
             GROUP BY e.id, e.title
             HAVING min_p >= ? AND max_p <= ?
             ORDER BY e.start_datetime ASC
             LIMIT 100",
            [$min, $max],
        ];
    }

    /** Matches exactly the subquery shape used in EventCalendarService. */
    private function calendarJoinQuery(): array
    {
        return [
            "SELECT e.id, e.title, e.status, e.start_datetime,
                    COALESCE(inv.total_available_sum, 0) AS total_available,
                    COALESCE(inv.total_sold_sum, 0)      AS total_sold,
                    pw.min_price, pw.max_price
             FROM events e
             LEFT JOIN (
                SELECT event_id,
                       SUM(total_allocated - total_sold) AS total_available_sum,
                       SUM(total_sold)                   AS total_sold_sum
                FROM ticket_inventory
                GROUP BY event_id
             ) inv ON inv.event_id = e.id
             LEFT JOIN (
                SELECT event_id, MIN(price) AS min_price, MAX(price) AS max_price
                FROM pricing_windows
                WHERE is_active = 1 AND deleted_at IS NULL
                GROUP BY event_id
             ) pw ON pw.event_id = e.id
             WHERE e.status = ?
               AND e.start_datetime >= ?
               AND e.start_datetime <= ?
             ORDER BY e.start_datetime ASC
             LIMIT 200",
            ['published', '2024-01-01 00:00:00', '2024-12-31 23:59:59'],
        ];
    }
}
