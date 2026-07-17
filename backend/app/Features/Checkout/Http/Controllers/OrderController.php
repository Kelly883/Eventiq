<?php

namespace App\Features\Checkout\Http\Controllers;

use App\Features\Checkout\Http\Resources\OrderResource;
use App\Features\Checkout\Models\Order;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * GET /api/orders/{orderId} - order confirmation page data.
     */
    public function show(Request $request, int $orderId)
    {
        $order = Order::with(['items.ticketTier', 'tickets', 'event'])
            ->where('id', $orderId)
            ->where('user_id', $request->user()->id) // scoped to the authenticated user - not just any order id
            ->firstOrFail();

        return new OrderResource($order);
    }
}
