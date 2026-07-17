<?php

namespace App\Features\Checkout\Http\Controllers;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\OrderItem;
use App\Features\Checkout\Models\Payment;
use App\Features\Payment\Services\FlutterwaveService;
use App\Features\Payment\Services\PaystackService;
use App\Http\Controllers\Controller;
use App\Models\TicketTier;
use App\Features\Inventory\Models\TicketInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function __construct(
        private PaystackService $paystack,
        private FlutterwaveService $flutterwave,
    ) {
    }

    /**
     * POST /api/checkout/create-payment-intent
     *
     * Creates a pending order, then initializes the transaction with the
     * chosen gateway (paystack|flutterwave). Returns whatever the gateway
     * needs the frontend to redirect to / initialize its popup with -
     * there's no Stripe-style client_secret here, since Paystack returns
     * an authorization_url + access_code and Flutterwave returns a link.
     */
    public function createPaymentIntent(Request $request)
    {
        $validated = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'gateway' => ['required', 'in:paystack,flutterwave'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.ticket_tier_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $user = $request->user();

        // Re-verify prices/availability server-side - never trust amounts
        // the client sends, even if CartController::verify was already
        // called earlier in the flow (data can go stale between calls).
        $lineItems = [];
        $total = 0;

        foreach ($validated['items'] as $item) {
            $tier = TicketTier::findOrFail($item['ticket_tier_id']);
            $inventory = TicketInventory::where('ticket_tier_id', $tier->id)->first();
            $remaining = $inventory?->remaining ?? $tier->capacity;

            if ($remaining < $item['quantity']) {
                return response()->json([
                    'message' => "Only {$remaining} left for {$tier->name}, requested {$item['quantity']}.",
                ], 422);
            }

            $unitPrice = ($tier->early_bird_price && $tier->early_bird_end_date && now()->lt($tier->early_bird_end_date))
                ? $tier->early_bird_price
                : $tier->price;

            $lineItems[] = ['tier' => $tier, 'quantity' => $item['quantity'], 'unit_price' => $unitPrice];
            $total += $unitPrice * $item['quantity'];
        }

        $reference = 'ord_' . Str::uuid();

        $order = DB::transaction(function () use ($user, $validated, $total, $reference, $lineItems) {
            $order = Order::create([
                'user_id' => $user->id,
                'event_id' => $validated['event_id'],
                'status' => 'pending',
                'total_amount' => $total,
                'currency' => config('payment.currency', 'NGN'),
                'payment_gateway' => $validated['gateway'],
                'payment_reference' => $reference,
                'ip_address' => request()->ip(),
            ]);

            foreach ($lineItems as $line) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'ticket_tier_id' => $line['tier']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                ]);
            }

            return $order;
        });

        $gatewayService = $validated['gateway'] === 'paystack' ? $this->paystack : $this->flutterwave;

        try {
            $gatewayData = $gatewayService->initializeTransaction([
                'email' => $user->email,
                'amount' => $total,
                'reference' => $reference,
                'callback_url' => config('app.url') . '/checkout/callback',
                'metadata' => ['order_id' => $order->id],
            ]);
        } catch (\Throwable $e) {
            // Gateway call failed - mark the order failed rather than
            // leaving it stuck 'pending' forever with no way forward.
            $order->update(['status' => 'failed']);
            Log::error('CheckoutController: gateway initialization failed for order ' . $order->id . ': ' . $e->getMessage());

            return response()->json(['message' => 'Unable to initialize payment. Please try again.'], 502);
        }

        Payment::create([
            'order_id' => $order->id,
            'gateway_reference' => $reference,
            'amount' => $total,
            'currency' => $order->currency,
            'status' => 'pending',
            'gateway' => $validated['gateway'],
            'gateway_response' => $gatewayData,
        ]);

        return response()->json([
            'order_id' => $order->id,
            'reference' => $reference,
            'gateway' => $validated['gateway'],
            'gateway_data' => $gatewayData, // authorization_url/access_code (Paystack) or link (Flutterwave)
        ]);
    }
}
