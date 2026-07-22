<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AggregateAnalyticsSalesTimeline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Pre-aggregates raw sales_timeline data into hourly/daily summary rows
     * to avoid expensive SUM()/GROUP BY queries on millions of rows at runtime.
     *
     * @signature analytics:aggregate-sales {--interval=daily} {--from=} {--to=}
     */
    protected $signature = 'analytics:aggregate-sales
                            {--interval=daily : Aggregation interval: "hourly" or "daily"}
                            {--from= : Start date (Y-m-d or Y-m-d H:i:s). Defaults to 30 days ago}
                            {--to= : End date. Defaults to now}';

    protected $description = 'Pre-aggregate analytics_sales_timeline into summary rows for fast dashboard queries';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $interval = $this->option('interval');
        if (!in_array($interval, ['hourly', 'daily'], true)) {
            $this->error("Invalid interval '{$interval}'. Use 'hourly' or 'daily'.");
            return Command::FAILURE;
        }

        $from = $this->option('from')
            ? Carbon::parse($this->option('from'))
            : Carbon::now()->subDays(30);

        $to = $this->option('to')
            ? Carbon::parse($this->option('to'))
            : Carbon::now();

        $this->info("=== Analytics Sales Timeline Aggregator ===");
        $this->newLine();
        $this->line("Interval: {$interval}");
        $this->line("From:     {$from->toDateTimeString()}");
        $this->line("To:       {$to->toDateTimeString()}");
        $this->newLine();

        $driver = DB::connection()->getDriverName();

        if ($interval === 'hourly') {
            $timeBucketExpr = match ($driver) {
                'sqlite' => "strftime('%Y-%m-%d %H:00:00', sale_timestamp)",
                'mysql'  => "DATE_FORMAT(sale_timestamp, '%Y-%m-%d %H:00:00')",
                'pgsql'  => "date_trunc('hour', sale_timestamp)",
                default  => "strftime('%Y-%m-%d %H:00:00', sale_timestamp)",
            };
            $summaryTable = 'analytics_sales_hourly_summary';
        } else {
            $timeBucketExpr = match ($driver) {
                'sqlite' => "strftime('%Y-%m-%d', sale_timestamp)",
                'mysql'  => "DATE_FORMAT(sale_timestamp, '%Y-%m-%d')",
                'pgsql'  => "date_trunc('day', sale_timestamp)",
                default  => "strftime('%Y-%m-%d', sale_timestamp)",
            };
            $summaryTable = 'analytics_sales_daily_summary';
        }

        // Ensure summary table exists
        $this->ensureSummaryTableExists($summaryTable, $interval);

        // Clear existing summaries for the time range to avoid duplicates on re-run
        $this->line("Clearing existing summaries in range...");
        DB::table($summaryTable)
            ->where('time_bucket', '>=', $from)
            ->where('time_bucket', '<=', $to)
            ->delete();

        // Perform aggregation
        $this->line("Aggregating raw sales data...");

        $aggregated = DB::table('analytics_sales_timeline')
            ->select(
                DB::raw("{$timeBucketExpr} as time_bucket"),
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total_amount) as total_revenue'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('AVG(unit_price) as average_unit_price')
            )
            ->where('sale_timestamp', '>=', $from)
            ->where('sale_timestamp', '<=', $to)
            ->groupBy('time_bucket')
            ->orderBy('time_bucket')
            ->get();

        if ($aggregated->isEmpty()) {
            $this->warn("No sales data found in the specified range.");
            return Command::SUCCESS;
        }

        $inserted = 0;
        $chunk = [];

        foreach ($aggregated as $row) {
            $chunk[] = [
                'time_bucket'      => $row->time_bucket,
                'total_quantity'   => (int) $row->total_quantity,
                'total_revenue'    => (float) $row->total_revenue,
                'transaction_count' => (int) $row->transaction_count,
                'avg_unit_price'   => round((float) $row->average_unit_price, 2),
                'aggregated_at'    => now(),
            ];

            if (count($chunk) >= 100) {
                DB::table($summaryTable)->insert($chunk);
                $inserted += count($chunk);
                $chunk = [];
            }
        }

        if (!empty($chunk)) {
            DB::table($summaryTable)->insert($chunk);
            $inserted += count($chunk);
        }

        $this->newLine();
        $this->info("Aggregation complete! {$inserted} summary rows inserted into '{$summaryTable}'.");
        $this->line("Range: {$from->toDateTimeString()} → {$to->toDateTimeString()}");

        return Command::SUCCESS;
    }

    /**
     * Create the summary table if it doesn't exist.
     */
    private function ensureSummaryTableExists(string $tableName, string $interval): void
    {
        if (DB::getSchemaBuilder()->hasTable($tableName)) {
            return;
        }

        $this->line("Creating summary table '{$tableName}'...");

        DB::getSchemaBuilder()->create($tableName, function ($table) use ($interval) {
            $table->id();
            $table->timestamp('time_bucket')->index();
            $table->integer('total_quantity')->default(0);
            $table->decimal('total_revenue', 14, 2)->default(0);
            $table->integer('transaction_count')->default(0);
            $table->decimal('avg_unit_price', 10, 2)->default(0);
            $table->timestamp('aggregated_at')->nullable();
            $table->timestamps();

            // Composite for range queries
            $table->index('time_bucket', "idx_{$tableName}_time_bucket");
        });

        $this->info("Summary table '{$tableName}' created.");
    }
}

