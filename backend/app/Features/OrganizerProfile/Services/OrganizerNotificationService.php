<?php

namespace App\Features\OrganizerProfile\Services;

use App\Features\Checkout\Models\Order;
use Illuminate\Support\Facades\Log;

class OrganizerNotificationService
{
    /**
     * Send notification to organizer about successful payment.
     */
    public function notifyPaymentSuccess(Order $order): void
    {
        $event = $order->event;
        if (!$event) {
            Log::warning('OrganizerNotificationService: Order has no event', ['order_id' => $order->id]);
            return;
        }

        $organizer = $event->organizer;
        if (!$organizer) {
            Log::warning('OrganizerNotificationService: Event has no organizer', ['event_id' => $event->id]);
            return;
        }

        Log::info('OrganizerNotificationService: Payment success notification', [
            'order_id' => $order->id,
            'organizer_id' => $organizer->id,
            'event_id' => $event->id,
            'event_title' => $event->title,
            'total_amount' => $order->total_amount,
            'ticket_count' => $order->items->sum('quantity'),
            'customer_email' => $order->user->email,
        ]);

        // In production, dispatch a job to send email/push notification
        // This is a placeholder - the actual notification channel depends on
        // what the organizer has configured (email, webhook, dashboard notification, etc.)
    }
}