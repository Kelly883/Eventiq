<?php

namespace App\Features\Compliance\Jobs;

use App\Features\CheckIn\Models\CheckIn;
use App\Features\Checkout\Models\Ticket;
use App\Features\Compliance\Services\AuditLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class PurgeTicketJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    public function __construct(
        public string $ticketId,
        public ?string $reason = null,
        public ?string $adminId = null,
    ) {
        $this->onQueue(config('queue.compliance_queue', 'compliance'));
    }

    public function handle(AuditLogService $auditLogService): void
    {
        $ticket = Ticket::find($this->ticketId);

        if (!$ticket) {
            return;
        }

        DB::transaction(function () use ($ticket, $auditLogService) {
            $oldValues = [
                'ticket_id' => $ticket->ticket_id,
                'attendee_name' => $ticket->attendee_name,
                'attendee_email' => $ticket->attendee_email,
                'qr_code_data' => $ticket->qr_code_data ? '[REDACTED]' : null,
                'qr_code_secret' => $ticket->qr_code_secret ? '[REDACTED]' : null,
                'status' => $ticket->status,
                'checked_in' => $ticket->checked_in,
                'checked_in_at' => $ticket->checked_in_at,
                'checked_in_by' => $ticket->checked_in_by,
            ];

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

            $checkInCount = CheckIn::where('ticket_id', $ticket->id)->count();

            $auditLogService->log(
                'ticket.purged',
                'ticket',
                $ticket->id,
                [
                    'ticket_id' => $ticket->ticket_id,
                    'event_id' => $ticket->event_id,
                    'reason' => $this->reason,
                    'old_values' => $oldValues,
                    'check_in_records_preserved' => $checkInCount,
                    'admin_id' => $this->adminId,
                ]
            );
        });
    }
}
