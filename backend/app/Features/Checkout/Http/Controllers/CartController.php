<?php

namespace App\Features\Checkout\Http\Controllers;

use App\Features\Checkout\Models\OrderItem;
use App\Features\Checkout\Models\Ticket;
use App\Http\Controllers\Controller;
use App\Models\TicketTier;
use App\Features\Inventory\Models\TicketInventory;
use Illuminate\Http\Request;

class CartController extends Controller
{
    const CART_SESSION_KEY = 'cart_items';

    /**
     * GET /api/cart - returns the authenticated user's persisted cart items.
     */
    public function index(Request $request)
    {
        $cart = $request->session()->get(self::CART_SESSION_KEY, []);

        return response()->json(['data' => $cart]);
    }

    /**
     * PUT /api/cart - persists the authenticated user's cart items to the session.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'cart' => ['required', 'array'],
            'cart.*.ticketTierId' => ['sometimes', 'string'],
            'cart.*.quantity' => ['sometimes', 'integer', 'min:1'],
            'cart.*.unitPrice' => ['sometimes', 'numeric'],
        ]);

        $request->session()->put(self::CART_SESSION_KEY, $validated['cart']);

        return response()->json(['data' => $validated['cart'], 'message' => 'Cart saved.']);
    }

    /**
     * POST /api/cart/verify - re-checks prices and availability against
     * the database before checkout, so a stale cart (price changed,
     * tier sold out since the user loaded the page) doesn't get charged
     * incorrectly.
     */
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.ticket_tier_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $results = [];
        $valid = true;
        $total = 0;

        foreach ($validated['items'] as $item) {
            $tier = TicketTier::find($item['ticket_tier_id']);

            if (! $tier || ! $tier->is_active) {
                $results[] = [
                    'ticket_tier_id' => $item['ticket_tier_id'],
                    'valid' => false,
                    'reason' => 'Ticket tier not found or no longer available.',
                ];
                $valid = false;
                continue;
            }

            $inventory = TicketInventory::where('ticket_tier_id', $tier->id)->first();

            // Real-time availability mirrors CheckoutController: prefer the
            // tier's own quantity/sold_count (DB-level source of truth),
            // falling back to TicketInventory.remaining, then capacity.
            $availableCount = $tier->quantity !== null
                ? (int) $tier->quantity - (int) $tier->sold_count
                : null;
            $remaining = $availableCount ?? $inventory?->remaining ?? $tier->quantity;

            if ($remaining < $item['quantity']) {
                $results[] = [
                    'ticket_tier_id' => $tier->id,
                    'valid' => false,
                    'reason' => "Only {$remaining} left, requested {$item['quantity']}.",
                ];
                $valid = false;
                continue;
            }

            // Honor early-bird pricing if still active, matching TicketTier's
            // own fields (early_bird_price / early_bird_end_date).
            $unitPrice = ($tier->early_bird_price && $tier->early_bird_end_date && now()->lt($tier->early_bird_end_date))
                ? $tier->early_bird_price
                : $tier->price;

            $lineTotal = $unitPrice * $item['quantity'];
            $total += $lineTotal;

            $results[] = [
                'ticket_tier_id' => $tier->id,
                'valid' => true,
                'quantity' => $item['quantity'],
                'unit_price' => (float) $unitPrice,
                'line_total' => (float) $lineTotal,
            ];
        }

        return response()->json([
            'valid' => $valid,
            'items' => $results,
            'total' => (float) $total,
        ], $valid ? 200 : 422);
    }
}
