<?php

namespace App\Features\Checkout\Http\Controllers;

use App\Features\Checkout\Models\Ticket;
use App\Features\Delivery\Models\DeliveryEvent;
use App\Http\Controllers\Controller;
use App\Services\TicketDeliveryService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * GET /api/tickets
     *
     * PRD: checkout_endpoint_get_tickets -- flat list of the authenticated
     * user's tickets for the "My Tickets" dashboard.
     */
    public function index(Request $request)
    {
        $tickets = Ticket::with(['event', 'ticketTier'])
            ->forUser($request->user()->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Ticket $t) => $this->toTicketArray($t))
            ->values();

        return response()->json(['data' => $tickets]);
    }

    /**
     * GET /api/tickets/{identifier}
     *
     * PRD: delivery_endpoint_ticket_status + checkout single-ticket endpoint.
     * The identifier may be the UUID id OR the human ticket_id reference code.
     * Pages read response.data as the ticket object (no resource envelope).
     */
    public function show(Request $request, string $identifier)
    {
        $ticket = $this->resolveTicket($identifier);

        if (! $ticket) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        $this->authorizeTicketAccess($ticket, $request->user());

        return response()->json($this->toTicketArray($ticket));
    }

    /**
     * POST /api/tickets/{identifier}/resend-{email|sms|dashboard}
     * PRD: delivery_endpoint_resend_ticket. Records a DeliveryEvent audit row
     * and dispatches through TicketDeliveryService (single provider path).
     */
    public function resend(Request $request, string $identifier, string $channel)
    {
        if (! in_array($channel, ['email', 'sms', 'dashboard'])) {
            return response()->json(['message' => "Unsupported delivery channel: {$channel}"], 422);
        }

        $ticket = $this->resolveTicket($identifier);

        if (! $ticket) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        $this->authorizeTicketAccess($ticket, $request->user());

        $result = app(TicketDeliveryService::class)->send($channel, $this->deliveryPayload($ticket, $channel));

        $this->recordDeliveryEvent($ticket, $channel, $result);

        return response()->json(['data' => ['success' => true, 'channel' => $channel]]);
    }

    /** Resolve a ticket by UUID id or by human reference code (ticket_id). */
    private function resolveTicket(string $identifier): ?Ticket
    {
        $ticket = Ticket::with(['event', 'ticketTier', 'user'])->whereKey($identifier)->first();
        if (! $ticket) {
            $ticket = Ticket::with(['event', 'ticketTier', 'user'])
                ->where('ticket_id', $identifier)
                ->first();
        }

        return $ticket;
    }

    private function authorizeTicketAccess(Ticket $ticket, $user): void
    {
        if (! $user || ($ticket->user_id !== $user->id && ! $user->hasRole('admin'))) {
            abort(403, 'You do not have access to this ticket.');
        }
    }

    private function deliveryPayload(Ticket $ticket, string $channel): array
    {
        $user = $ticket->user;
        $email = $ticket->attendee_email ?? optional($user)->email;
        $tierName = $ticket->ticketTier->name ?? $ticket->tier ?? null;

        $payload = [
            'ticket_reference' => $ticket->ticket_id ?? $ticket->id,
            'user_id' => $ticket->user_id,
            'payload' => ['event' => $ticket->event->title ?? null],
        ];

        if ($channel === 'email') {
            $payload['to'] = $email;
            $payload['subject'] = $tierName ? "Your ticket - {$tierName}" : 'Your ticket';
            $payload['body'] = "Ticket reference: " . ($ticket->ticket_id ?? $ticket->id);
        } elseif ($channel === 'sms') {
            $payload['to'] = null;
            $payload['message'] = "Your ticket: " . ($ticket->ticket_id ?? $ticket->id);
        }

        return $payload;
    }

    private function recordDeliveryEvent(Ticket $ticket, string $channel, array $result): void
    {
        $delivered = ! empty($result['sent']) || ! empty($result['recorded']);

        try {
            DeliveryEvent::create([
                'ticket_id' => $ticket->id,
                'user_id' => $ticket->user_id,
                'event_id' => $ticket->event_id,
                'channel' => $channel,
                'status' => $delivered ? 'sent' : 'failed',
                'ticket_reference' => $ticket->ticket_id ?? $ticket->id,
                'recipient' => $ticket->attendee_email ?? null,
                'payload' => $result,
            ]);
        } catch (\Throwable $e) {
            // Recording is best-effort; delivery still proceeds.
        }
    }

    /**
     * Shape a ticket for the frontend (superset serving detail/status/delivery).
     */
    private function toTicketArray(Ticket $ticket): array
    {
        $user = $ticket->user;
        $event = $ticket->event;
        $tier = $ticket->ticketTier;

        $deliveries = DeliveryEvent::where('ticket_id', $ticket->id)
            ->orderByDesc('created_at')
            ->get();

        $deliveryStatus = [];
        foreach (['email', 'sms', 'dashboard'] as $channel) {
            $last = $deliveries->firstWhere('channel', $channel);
            $deliveryStatus[$channel] = [
                'sent' => $last && in_array($last->status, ['sent', 'delivered', 'opened'], true),
                'timestamp' => $last ? $last->created_at->toDateTimeString() : null,
            ];
        }

        $code = $ticket->ticket_id ?? null;
        $createdAt = $ticket->created_at ? $ticket->created_at->toDateTimeString() : null;

        return [
            'id' => $ticket->id,
            'code' => $code ?? str_replace('-', '', $ticket->id),
            'ticket_id' => $ticket->ticket_id,
            'status' => $ticket->status,
            'created_at' => $createdAt,
            'createdAt' => $createdAt,
            'type' => $tier->name ?? $ticket->tier ?? null,
            'tier' => $tier->name ?? $ticket->tier ?? null,
            'ticket_tier' => $tier ? $tier->name : null,
            'eventName' => $event->title ?? null,
            'event_name' => $event->title ?? null,
            'eventDate' => $event->start_datetime ?? null,
            'venue' => $event->venue_name ?? null,
            'venue_name' => $event->venue_name ?? null,
            'holderName' => $ticket->attendee_name ?? optional($user)->name ?? null,
            'holder_name' => $ticket->attendee_name ?? optional($user)->name ?? null,
            'attendee_name' => $ticket->attendee_name,
            'attendee_email' => $ticket->attendee_email,
            'qr_code_data' => $ticket->qr_code_data,
            'qr_code' => $ticket->qr_code_data,
            'qr_code_scanned_count' => (int) $ticket->qr_code_scanned_count,
            'checked_in' => (bool) $ticket->checked_in,
            'checked_in_at' => $ticket->checked_in_at,
            'refund_status' => $ticket->refund_status,
            'deliveryStatus' => $deliveryStatus,
            'event' => $event ? [
                'id' => $event->id,
                'title' => $event->title,
                'start_datetime' => $event->start_datetime,
                'venue_name' => $event->venue_name,
                'venue_address' => $event->venue_address,
            ] : null,
            'events' => $deliveries->map(fn (DeliveryEvent $d) => [
                'id' => $d->id,
                'channel' => $d->channel,
                'status' => $d->status,
                'created_at' => $d->created_at ? $d->created_at->toDateTimeString() : null,
            ])->values(),
        ];
    }
}

