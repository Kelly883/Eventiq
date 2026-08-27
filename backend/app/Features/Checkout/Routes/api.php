<?php

use App\Features\Checkout\Http\Controllers\CartController;
use App\Features\Checkout\Http\Controllers\CheckoutController;
use App\Features\Checkout\Http\Controllers\MyTicketsController;
use App\Features\Checkout\Http\Controllers\OrderController;
use App\Features\Checkout\Http\Controllers\TicketController;
use App\Features\Checkout\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Webhook is intentionally unauthenticated (called by the payment
// provider, not a logged-in user) - signature verification inside the
// controller is what actually protects it.
Route::post('/webhooks/payment-provider', [WebhookController::class, 'handle']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/cart/verify', [CartController::class, 'verify']);
    Route::post('/checkout/create-payment-intent', [CheckoutController::class, 'createPaymentIntent']);
        Route::get('/orders/{orderId}', [OrderController::class, 'show']);
    Route::get('/my-tickets', [MyTicketsController::class, 'index']);

    // PRD: checkout_endpoint_get_tickets / delivery_endpoint_ticket_status /
    // delivery_endpoint_resend_ticket. Note /orders/{orderId}/tickets (the PRD
    // per-order variant) is intentionally not added here -- the per-order
    // route would collide with /tickets/{identifier} above.
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::get('/tickets/{identifier}', [TicketController::class, 'show']);
    Route::post('/tickets/{identifier}/resend-email', [TicketController::class, 'resend']);
    Route::post('/tickets/{identifier}/resend-sms', [TicketController::class, 'resend']);
    Route::post('/tickets/{identifier}/resend-dashboard', [TicketController::class, 'resend']);
});
