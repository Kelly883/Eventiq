<?php

namespace App\Features\Delivery\Controllers;

use App\Features\Checkout\Models\Ticket;
use App\Features\Delivery\Models\DeliveryEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DeliveryController extends Controller
{
    /**
     * GET /api/tickets/:ticketId/delivery-status
     *
     * Returns the delivery status and full delivery history for a ticket.
     * Only the ticket owner or admin can access.
     */
    public function status(Request $request, string $ticketId)
    {
        $ticket = Ticket::with(['event', 'ticketTier'])->whereKey($ticketId)->first();

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        // Ownership check
        if (!($ticket->user_id === $request->user()?->id || $request->user()?->hasRole('admin'))) {
            return response()->json(['message' => 'You do not have access to this ticket.'], 403);
        }

        $deliveryEvents = DeliveryEvent::where('ticket_id', $ticket->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => [
                'ticket_id' => $ticket->id,
                'ticket_reference' => $ticket->ticket_id,
                'status' => $ticket->status,
                'delivery_history' => $deliveryEvents->map(fn (DeliveryEvent $d) => [
                    'id' => $d->id,
                    'channel' => $d->channel,
                    'status' => $d->status,
                    'recipient' => $d->recipient,
                    'attempt_count' => $d->attempt_count,
                    'max_attempts' => $d->max_attempts,
                    'error_message' => $d->error_message,
                    'last_attempt_at' => $d->last_attempt_at?->toDateTimeString(),
                    'delivered_at' => $d->delivered_at?->toDateTimeString(),
                    'next_retry_at' => $d->next_retry_at?->toDateTimeString(),
                    'created_at' => $d->created_at?->toDateTimeString(),
                ]),
            ],
        ]);
    }

    /**
     * POST /api/tickets/:ticketId/resend-delivery
     *
     * Resend a ticket delivery. Updates the latest delivery event status
     * to 'pending' and sets next_retry_at.
     */
    public function resend(Request $request, string $ticketId)
    {
        $request->validate([
            'channel' => ['required', 'in:email,sms,dashboard'],
            'recipient' => ['required', 'string'],
        ]);

        $ticket = Ticket::with(['event', 'ticketTier', 'order'])->whereKey($ticketId)->first();

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        // Ownership check
        if (!($ticket->user_id === $request->user()?->id || $request->user()?->hasRole('admin'))) {
            return response()->json(['message' => 'You do not have access to this ticket.'], 403);
        }

        // Validate recipient email if channel is email
        $recipient = $request->input('recipient');
        $channel = $request->input('channel');

        if ($channel === 'email' && !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'Invalid email format.'], 400);
        }

        // Check fraud-blocked order
        if (in_array($ticket->status, ['fraud_flagged', 'suspicious', 'void'], true)) {
            return response()->json([
                'message' => 'Cannot resend delivery for a blocked ticket. Contact support.',
                'reason' => 'ticket_blocked',
            ], 403);
        }

        // Find latest delivery event for this ticket and channel
        $latestEvent = DeliveryEvent::where('ticket_id', $ticket->id)
            ->where('channel', $channel)
            ->orderByDesc('created_at')
            ->first();

        // Check max attempts
        if ($latestEvent && $latestEvent->attempt_count >= $latestEvent->max_attempts) {
            return response()->json([
                'message' => 'Maximum delivery attempts exceeded. Contact support.',
                'reason' => 'max_attempts_exceeded',
            ], 400);
        }

        // Create new delivery event or reset existing one
        $nextRetryAt = now()->addMinutes(5);

        if ($latestEvent && in_array($latestEvent->status, ['failed', 'pending'], true)) {
            // Reset the existing event
            $latestEvent->update([
                'status' => 'pending',
                'recipient' => $recipient,
                'next_retry_at' => $nextRetryAt,
                'last_attempt_at' => now(),
                'attempt_count' => $latestEvent->attempt_count + 1,
                'error_message' => null,
            ]);
        } else {
            // Create a new delivery event
            $latestEvent = DeliveryEvent::create([
                'ticket_id' => $ticket->id,
                'user_id' => $ticket->user_id,
                'event_id' => $ticket->event_id,
                'order_id' => $ticket->order_id,
                'channel' => $channel,
                'status' => 'pending',
                'ticket_reference' => $ticket->ticket_id ?? $ticket->id,
                'recipient' => $recipient,
                'subject' => 'Your ticket - ' . ($ticket->ticketTier->name ?? 'event'),
                'body' => 'Ticket reference: ' . ($ticket->ticket_id ?? $ticket->id),
                'attempt_count' => 1,
                'max_attempts' => 3,
                'last_attempt_at' => now(),
                'next_retry_at' => $nextRetryAt,
            ]);
        }

        Log::info('DeliveryController: Resend delivery initiated', [
            'ticket_id' => $ticket->id,
            'channel' => $channel,
            'recipient' => $recipient,
            'next_retry_at' => $nextRetryAt->toDateTimeString(),
        ]);

        return response()->json([
            'data' => [
                'message' => 'Delivery resend initiated.',
                'delivery_event_id' => $latestEvent->id,
                'status' => 'pending',
                'next_retry_at' => $nextRetryAt->toDateTimeString(),
            ],
        ]);
    }
}
