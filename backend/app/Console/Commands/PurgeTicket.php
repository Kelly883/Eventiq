<?php

namespace App\Console\Commands;

use App\Features\Checkout\Models\Ticket;
use App\Features\CheckIn\Models\CheckIn;
use App\Features\Compliance\Jobs\PurgeTicketJob;
use App\Features\Compliance\Services\AuditLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeTicket extends Command
{
    protected $signature = 'tickets:purge
        {id? : Ticket UUID to purge}
        {--event_id= : Purge all tickets for an event}
        {--before= : Purge tickets created before this date (Y-m-d)}
        {--chunk-size=50 : Number of tickets to process per chunk}
        {--dry-run : Preview purge without making changes}
        {--force : Execute purge without interactive confirmation}
        {--queue : Dispatch purge jobs to queue for large batches}';

    protected $description = 'Anonymize ticket PII while preserving check-in records for compliance.';

    public function __construct(
        private AuditLogService $auditLogService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $ticketId = $this->argument('id');
        $eventId = $this->option('event_id');
        $before = $this->option('before');
        $chunkSize = (int) $this->option('chunk-size');
        $dryRun = $this->option('dry-run');
        $useQueue = $this->option('queue');

        if (!$ticketId && !$eventId && !$before) {
            $this->error('Provide a ticket id, --event_id, or --before date.');
            return self::FAILURE;
        }

        if (!$dryRun && !$this->option('force')) {
            if (!$this->confirm('This will permanently anonymize ticket PII. Continue?')) {
                $this->info('Aborted.');
                return self::SUCCESS;
            }
        }

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be made.');
        }

        $query = Ticket::query();

        if ($ticketId) {
            $query->where('id', $ticketId);
        }

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        if ($before) {
            $query->where('created_at', '<', $before . ' 00:00:00');
        }

        $total = $query->count();
        $this->info("Found {$total} ticket(s) to process.");

        if ($total === 0) {
            return self::SUCCESS;
        }

        $queueThreshold = 100;

        if ($useQueue && $total > $queueThreshold) {
            $this->info("Dispatching purge jobs to queue (batch > {$queueThreshold})...");

            $query->orderBy('id')->chunkById($chunkSize, function ($tickets) {
                foreach ($tickets as $ticket) {
                    PurgeTicketJob::dispatch($ticket->id);
                    $this->line("Dispatched purge job for ticket {$ticket->ticket_id} ({$ticket->id}).");
                }
            });

            $this->info("Dispatched purge jobs for {$total} ticket(s).");
            $this->info('Run `php artisan queue:work` to process them.');
            return self::SUCCESS;
        }

        $processed = 0;
        $preservedCheckIns = 0;

        $query->orderBy('id')->chunkById($chunkSize, function ($tickets) use ($dryRun, &$processed, &$preservedCheckIns) {
            foreach ($tickets as $ticket) {
                $checkInCount = CheckIn::where('ticket_id', $ticket->id)->count();

                if ($dryRun) {
                    $this->line("[DRY RUN] Would purge ticket {$ticket->ticket_id} ({$ticket->id}) — {$checkInCount} check-in(s) preserved.");
                    $processed++;
                    $preservedCheckIns += $checkInCount;
                    continue;
                }

                DB::transaction(function () use ($ticket, $checkInCount) {
                    $ticket->attendee_name = null;
                    $ticket->attendee_email = null;
                    $ticket->qr_code_data = null;
                    $ticket->qr_code_secret = null;
                    $ticket->status = 'purged';
                    $ticket->checked_in = false;
                    $ticket->checked_in_at = null;
                    $ticket->checked_in_by = null;
                    $ticket->qr_code_generated_at = null;
                    $ticket->qr_code_expires_at = null;
                    $ticket->qr_code_scanned_count = 0;
                    $ticket->last_qr_scan_at = null;
                    $ticket->first_scanned_at = null;
                    $ticket->save();

                    $this->auditLogService->log(
                        'ticket.purged',
                        'ticket',
                        $ticket->id,
                        [
                            'ticket_id' => $ticket->ticket_id,
                            'event_id' => $ticket->event_id,
                            'check_in_records_preserved' => $checkInCount,
                        ]
                    );
                });

                $processed++;
                $preservedCheckIns += $checkInCount;
                $this->line("Purged ticket {$ticket->ticket_id} — {$checkInCount} check-in(s) preserved.");
            }
        });

        $this->info("Processed {$processed} ticket(s).");
        $this->info("Total check-in records preserved: {$preservedCheckIns}");

        if (!$dryRun) {
            $this->info('All changes committed.');
        }

        return self::SUCCESS;
    }
}
