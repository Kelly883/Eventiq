<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class ArchiveAnalyticsSalesTimeline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Moves analytics_sales_timeline rows older than the specified retention period
     * to an archive table to keep the main table lean for fast time-series queries.
     */
    protected $signature = 'analytics:archive-sales
                            {--retention-months=12 : Number of months to retain in the main table}
                            {--dry-run : Preview rows that would be archived without making changes}';

    protected $description = 'Archive old analytics_sales_timeline rows to keep the main table performant';

    protected string $archiveTable = 'analytics_sales_timeline_archive';

    public function handle(): int
    {
        $retentionMonths = (int) $this->option('retention-months');
        $isDryRun = (bool) $this->option('dry-run');
        $cutoffDate = Carbon::now()->subMonths($retentionMonths);

        $this->info("=== Analytics Sales Timeline Archiver ===");
        $this->newLine();
        $this->line("Retention period: {$retentionMonths} months");
        $this->line("Cutoff date:      {$cutoffDate->toDateTimeString()}");
        $this->line("Mode:             " . ($isDryRun ? 'DRY RUN (no changes)' : 'LIVE'));
        $this->newLine();

        $this->ensureArchiveTableExists();

        $count = DB::table('analytics_sales_timeline')
            ->where('sale_timestamp', '<', $cutoffDate)
            ->count();

        if ($count === 0) {
            $this->info("No rows found older than {$retentionMonths} months. Nothing to archive.");
            return Command::SUCCESS;
        }

        $this->line("Rows to archive: {$count}");
        $this->newLine();

        if ($isDryRun) {
            $this->warn("DRY RUN - no changes were made. Run without --dry-run to archive.");
            return Command::SUCCESS;
        }

        if (!$this->option('quiet') && !$this->confirm("Archive {$count} rows? This will move them to '{$this->archiveTable}'.", true)) {
            $this->warn('Operation cancelled by user.');
            return Command::FAILURE;
        }

        $this->line('Archiving...');

        $totalArchived = 0;
        $startTime = microtime(true);
        $archiveTableName = $this->archiveTable;

        DB::transaction(function () use ($cutoffDate, &$totalArchived, $archiveTableName) {
            $chunkSize = 500;
            DB::table('analytics_sales_timeline')
                ->where('sale_timestamp', '<', $cutoffDate)
                ->orderBy('sale_timestamp')
                ->chunkById($chunkSize, function ($rows) use (&$totalArchived, $archiveTableName) {
                    $insertData = [];
                    $ids = [];
                    foreach ($rows as $row) {
                        $insertData[] = (array) $row;
                        $ids[] = $row->id;
                    }

                    DB::table($archiveTableName)->insert($insertData);
                    DB::table('analytics_sales_timeline')
                        ->whereIn('id', $ids)
                        ->delete();

                    $totalArchived += count($rows);
                    $this->line("    Archived {$totalArchived} rows so far...");
                });
        });

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->newLine();
        $this->info("Archiving complete! {$totalArchived} rows moved to '{$this->archiveTable}' in {$elapsed}s.");

        return Command::SUCCESS;
    }

    private function ensureArchiveTableExists(): void
    {
        if (Schema::hasTable($this->archiveTable)) {
            return;
        }

        $this->line("Creating archive table '{$this->archiveTable}'...");

        Schema::create($this->archiveTable, function ($table) {
            $table->uuid('id')->primary();
            $table->foreignId('event_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('ticket_tier_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('pricing_window_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('sale_timestamp');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_amount', 12, 2);
            $table->string('buyer_email', 255)->nullable();
            $table->string('source', 100)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('archived_at')->nullable();

            $table->index('event_id');
            $table->index('sale_timestamp');
            $table->index('ticket_tier_id');
            $table->index(['event_id', 'sale_timestamp'], 'idx_archive_event_timestamp');
            $table->index(['event_id', 'ticket_tier_id', 'sale_timestamp'], 'idx_archive_full');
        });

        $this->info("Archive table '{$this->archiveTable}' created.");
    }
}

