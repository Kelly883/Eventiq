<?php

namespace App\Features\Checkout\Http\Controllers;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Payment;
use App\Features\Checkout\Models\Ticket;
use App\Features\Delivery\Jobs\SendTicketDeliveryJob;
use App\Features\Inventory\Models\TicketInventory;
use App\Features\Payment\Services\FlutterwaveService;
use App\Features\Payment\Services\PaystackService;
use App\Features\QRCodeTicketing\Services\QRCodeService;
use App\Http\Controllers\Controller;
use App\Models\TicketTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private PaystackService $paystack,
        private FlutterwaveService $flutterwave,
        private QRCodeService $qrCodeService,
    ) {
    }

    /**
     * POST /api/webhooks/payment-provider - unified entry point for both
     * gateways (detects which one via header presence, since Paystack
     * sends x-paystack-signature and Flutterwave sends verif-hash).
     *
     * Defense in depth: verifies the webhook signature first, THEN
     * independently calls the gateway's own verify-transaction API rather
     * than trusting amounts/status straight from the webhook body -
     * signature verification proves the request came from the gateway,
     * not that the payload wasn't stale or the transaction is really
     * complete on the gateway's own records.
     */
    public function handle(Request $request)
    {
        $gateway = $this->detectGateway($request);

        if (! $gateway) {
            Log::warning('WebhookController: could not determine gateway from headers', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Unrecognized webhook source'], 400);
        }

        $reference = $this->verifyAndExtractReference($request, $gateway);

        if (! $reference) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $order = Order::where('payment_reference', $reference)->first();

        if (! $order) {
            Log::warning("WebhookController: no order found for reference {$reference}");

            return response()->json(['received' => true]); // 200 so the gateway doesn't retry forever on a ref we'll never recognize
        }

        if ($order->status === 'completed') {
            return response()->json(['received' => true]); // Already processed - webhook delivery isn't guaranteed exactly-once
        }

        try {
            $verification = $gateway === 'paystack'
                ? $this->paystack->verifyTransaction($reference)
                : $this->flutterwave->verifyTransaction($reference);
        } catch (\Throwable $e) {
            Log::error("WebhookController: gateway verification call failed for {$reference}: " . $e->getMessage());

            return response()->json(['message' => 'Verification failed'], 502);
        }

        $succeeded = $gateway === 'paystack'
            ? ($verification['status'] ?? null) === 'success'
            : ($verification['status'] ?? null) === 'successful';

        if (! $succeeded) {
            $order->update(['status' => 'failed']);
            Payment::where('order_id', $order->id)->update(['status' => 'failed', 'gateway_response' => $verification]);

            return response()->json(['received' => true]);
        }

        DB::transaction(function () use ($order, $verification, $gateway) {
            $order->update(['status' => 'completed']);
            Payment::where('order_id', $order->id)->update(['status' => 'success', 'gateway_response' => $verification]);

            foreach ($order->items as $item) {
                // Lock the ticket tier row to prevent concurrent sold_count updates
                $tier = TicketTier::where('id', $item->ticket_tier_id)
                    ->lockForUpdate()
                    ->first();

                // Lock the inventory row to prevent concurrent sold_quantity updates
                $inventory = TicketInventory::where('ticket_tier_id', $item->ticket_tier_id)
                    ->lockForUpdate()
                    ->first();

                for ($i = 0; $i < $item->quantity; $i++) {
                    $ticket = Ticket::create([
                        'order_id' => $order->id,
                        'event_id' => $order->event_id,
                        'user_id' => $order->user_id,
                        'ticket_tier_id' => $item->ticket_tier_id,
                        'status' => 'valid',
                    ]);

                    $ticket->update(['qr_code' => $this->qrCodeService->generateForTicket($ticket)]);
                }

                // Atomically increment sold_count on ticket_tier with CHECK constraint enforcing sold_count <= quantity
                if ($tier) {
                    $tier->increment('sold_count', $item->quantity);
                }

                // Atomically increment total_sold on inventory
                if ($inventory) {
                    $inventory->increment('total_sold', $item->quantity);
                }
            }
        });

        SendTicketDeliveryJob::dispatch('email', [
            'to' => $order->user->email,
            'subject' => 'Your tickets for ' . ($order->event->title ?? 'your event'),
            'body' => "Thanks for your purchase! Your tickets are ready - view them in your dashboard.",
        ]);

        return response()->json(['received' => true]);
    }

    private function detectGateway(Request $request): ?string
    {
        if ($request->hasHeader('x-paystack-signature')) return 'paystack';
        if ($request->hasHeader('verif-hash')) return 'flutterwave';

        return null;
    }

    private function verifyAndExtractReference(Request $request, string $gateway): ?string
    {
        if ($gateway === 'paystack') {
            $valid = $this->paystack->verifyWebhookSignature(
                $request->getContent(),
                $request->header('x-paystack-signature', '')
            );

            return $valid ? ($request->input('data.reference')) : null;
        }

        $valid = $this->flutterwave->verifyWebhookSignature($request->header('verif-hash', ''));

        return $valid ? ($request->input('data.tx_ref') ?? $request->input('txRef')) : null;
    }
}
