<?php

namespace Tests\Feature;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Ticket;
use App\Features\Payment\Services\PaystackService;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class CheckoutControllerTest extends TestCase
{
    use RefreshDatabase;
    use MockeryPHPUnitIntegration;

    private function makeUser(string $role = 'attendee'): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'emailVerified' => true,
        ]);

        return $user;
    }

    private function seedTicketTier(User $organizer): TicketTier
    {
        $event = Event::factory()->create([
            'organizer_id' => $organizer->organizer()->create([
                'displayName' => $organizer->name,
            ])->id,
            'status' => 'published',
        ]);

        return TicketTier::factory()->create([
            'event_id' => $event->id,
            'status' => 'published',
            'quantity' => 100,
            'sold_count' => 0,
            'price' => 15000.00,
        ]);
    }

    public function test_cart_verify_requires_authentication(): void
    {
        $response = $this->postJson('/api/cart/verify', [
            'items' => [
                ['ticket_tier_id' => 1, 'quantity' => 1],
            ],
        ]);

        $response->assertUnauthorized();
    }

    public function test_create_payment_intent_requires_authentication(): void
    {
        $response = $this->postJson('/api/checkout/create-payment-intent', [
            'event_id' => 1,
            'gateway' => 'paystack',
            'items' => [
                ['ticket_tier_id' => 1, 'quantity' => 1],
            ],
        ]);

        $response->assertUnauthorized();
    }

    public function test_create_payment_intent_validates_missing_fields(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/checkout/create-payment-intent', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['event_id', 'gateway', 'items']);
    }

    public function test_create_payment_intent_returns_order_for_valid_cart(): void
    {
        $user = $this->makeUser();
        $tier = $this->seedTicketTier($user);

        $paystack = Mockery::mock(PaystackService::class);
        $paystack->shouldReceive('initializeTransaction')->once()->andReturn([
            'authorization_url' => 'https://paystack.com/pay/test',
            'access_code' => 'test-access-code',
            'reference' => 'test-reference',
        ]);
        $this->app->instance(PaystackService::class, $paystack);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/checkout/create-payment-intent', [
                'event_id' => $tier->event_id,
                'gateway' => 'paystack',
                'items' => [
                    ['ticket_tier_id' => $tier->id, 'quantity' => 1],
                ],
            ]);

        $response->assertOk()
            ->assertJsonStructure(['order_id', 'reference', 'gateway', 'gateway_data'])
            ->assertJsonPath('gateway', 'paystack');

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'event_id' => $tier->event_id,
            'status' => 'pending',
            'payment_gateway' => 'paystack',
        ]);
    }

    public function test_order_show_requires_authentication(): void
    {
        $order = Order::factory()->create();

        $response = $this->getJson("/api/orders/{$order->id}");

        $response->assertUnauthorized();
    }

    public function test_order_show_returns_404_for_other_users_order(): void
    {
        $owner = $this->makeUser();
        $otherUser = $this->makeUser();
        $tier = $this->seedTicketTier($owner);

        $order = Order::factory()->create([
            'user_id' => $owner->id,
            'event_id' => $tier->event_id,
        ]);

        $response = $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/orders/{$order->id}");

        $response->assertNotFound();
    }

    public function test_order_show_returns_order_for_owner(): void
    {
        $user = $this->makeUser();
        $tier = $this->seedTicketTier($user);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'event_id' => $tier->event_id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/orders/{$order->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $order->id);
    }

    public function test_my_tickets_requires_authentication(): void
    {
        $response = $this->getJson('/api/my-tickets');

        $response->assertUnauthorized();
    }

    public function test_my_tickets_scopes_to_authenticated_user(): void
    {
        $user = $this->makeUser();
        $otherUser = $this->makeUser();
        $tier = $this->seedTicketTier($user);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'event_id' => $tier->event_id,
        ]);

        Ticket::factory()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'event_id' => $tier->event_id,
            'ticket_tier_id' => $tier->id,
        ]);

        // Create a ticket for another user
        $otherTier = $this->seedTicketTier($otherUser);
        $otherOrder = Order::factory()->create([
            'user_id' => $otherUser->id,
            'event_id' => $otherTier->event_id,
        ]);

        Ticket::factory()->create([
            'order_id' => $otherOrder->id,
            'user_id' => $otherUser->id,
            'event_id' => $otherTier->event_id,
            'ticket_tier_id' => $otherTier->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/my-tickets');

        $response->assertOk();

        $tickets = $response->json('data');
        $this->assertCount(1, $tickets);
        $this->assertSame($tier->event_id, $tickets[0]['event']['id']);
    }
}
