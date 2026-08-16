<?php

namespace App\Features\admin\Controllers;

use App\Features\Checkout\Models\Ticket;
use App\Features\CheckIn\Models\CheckIn;
use App\Features\Compliance\Services\AuditLogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketPurgeController extends Controller
{
    public function __construct(
        private AuditLogService $auditLogService
    ) {}

    public function purge(Request $request, string $id)
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if (! uuid_is_valid($id)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid ticket ID format.',
            ], 422);
        }

        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found.',
            ], 404);
        }

        $adminId = $request->user()?->id;

        $purged = DB::transaction(function () use ($ticket, $adminId, $request) {
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

            $this->auditLogService->log(
                'ticket.purged',
                'ticket',
                $ticket->id,
                [
                    'ticket_id' => $ticket->ticket_id,
                    'event_id' => $ticket->event_id,
                    'reason' => $request->input('reason'),
                    'old_values' => $oldValues,
                    'check_in_records_preserved' => $checkInCount,
                    'admin_ip' => $request->ip(),
                    'admin_user_agent' => $request->userAgent(),
                ],
                $adminId
            );

            \Illuminate\Support\Facades\Log::info('DEBUG: audit log called', ['admin_id' => $adminId, 'ticket_id' => $ticket->id]);

            return $checkInCount;
        });

        return response()->json([
            'success' => true,
            'message' => 'Ticket purged successfully.',
            'ticket_id' => $ticket->ticket_id,
            'check_ins_preserved' => $purged,
        ]);
    }
}

