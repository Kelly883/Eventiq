<?php

namespace App\Features\Checkout\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TicketTier;
use App\Features\Inventory\Models\TicketInventory;
use Illuminate\Http\Request;

class CartController extends Controller
{
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
            $remaining = $inventory?->remaining ?? $tier->capacity;

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
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
        }

        return response()->json([
            'valid' => $valid,
            'items' => $results,
            'total' => $total,
        ], $valid ? 200 : 422);
    }
}
