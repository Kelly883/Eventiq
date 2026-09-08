<?php

namespace Tests\Feature;

use App\Models\Organizer;
use App\Models\Role;
use App\Models\User;
use App\Features\Payment\Models\OrganizerPayoutMethod;
use App\Features\Payment\Models\PaymentMethod;
use App\Features\Payment\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSettingsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['description' => $roleName]);
        $user = User::factory()->create();
        $user->roles()->attach($role);
        return $user;
    }

    private function makeOrganizerUser(): User
    {
        $user = $this->makeUserWithRole('organizer');
        Organizer::factory()->create([
            'user_id' => $user->id,
            'paystack_business_name' => 'Test Organizer Co',
            'paystack_subaccount_code' => 'SUB_TEST_0001',
            'paystack_recipient_code' => 'RCP_TEST_0001',
            'paystack_connect_status' => 'enabled',
            'flutterwave_subaccount_id' => 'FLW_TEST_REF_0001',
            'flutterwave_business_reference' => 'FLW_BIZ_REF_0001',
            'flutterwave_connect_status' => 'pending',
        ]);
        return $user;
    }

    public function test_guest_cannot_access_organizer_payment_settings(): void
    {
        $this->getJson('/api/organizer/payment-settings')->assertUnauthorized();
    }

    public function test_non_organizer_cannot_access_organizer_payment_settings(): void
    {
        $user = $this->makeUserWithRole('attendee');
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/organizer/payment-settings')
            ->assertForbidden();
    }

    public function test_organizer_can_view_their_payment_settings_without_secrets(): void
    {
        $user = $this->makeOrganizerUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/organizer/payment-settings')
            ->assertOk()
            ->assertJsonPath('paystackConnectStatus', 'enabled')
            ->assertJsonPath('paystackSubaccountCode', 'SUB_TEST_0001')
            ->assertJsonPath('paystackBusinessName', 'Test Organizer Co')
            ->assertJsonPath('isPaystackConnected', true)
            ->assertJsonPath('flutterwaveConnectStatus', 'pending')
            ->assertJsonPath('flutterwaveSubaccountId', 'FLW_TEST_REF_0001')
            ->assertJsonPath('isFlutterwaveConnected', false)
            ->assertJsonMissing(['secret_key', 'private_key', 'api_secret']);
    }

    public function test_organizer_cannot_self_assert_connect_status_or_write_payment_settings(): void
    {
        $user = $this->makeOrganizerUser();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/organizer/payment-settings', [
                'paystack_connect_status' => 'enabled',
                'paystack_subaccount_code' => 'SUB_SELF_ASSERTED',
                'paystack_recipient_code' => 'RCP_SELF_ASSERTED',
            ])
            ->assertStatus(405);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/organizer/payment-settings')
            ->assertOk()
            ->assertJsonPath('paystackConnectStatus', 'enabled')
            ->assertJsonPath('paystackSubaccountCode', 'SUB_TEST_0001')
            ->assertJsonPath('paystackRecipientCode', 'RCP_TEST_0001');
    }

    public function test_guest_cannot_access_payout_methods(): void
    {
        $this->getJson('/api/organizer/payout-methods')->assertUnauthorized();
    }

    public function test_non_organizer_cannot_access_payout_methods(): void
    {
        $user = $this->makeUserWithRole('attendee');
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/organizer/payout-methods')
            ->assertForbidden();
    }

    public function test_organizer_payout_methods_do_not_expose_full_account_number(): void
    {
        $user = $this->makeOrganizerUser();
        $organizer = $user->organizer;

        OrganizerPayoutMethod::create([
            'organizer_id' => $organizer->id,
            'bank_code' => '058',
            'bank_name' => 'GTBank',
            'account_number' => '0123456789',
            'account_name' => 'Test Organizer Co',
            'is_default' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/organizer/payout-methods')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.bank_name', 'GTBank')
            ->assertJsonPath('data.0.is_default', true)
            ->assertJsonMissing(['account_number'])
            ->assertJsonMissingPath('data.0.account_number');
    }

    public function test_guest_cannot_access_user_payment_methods(): void
    {
        $this->getJson('/api/user/payment-methods')->assertUnauthorized();
    }

    public function test_user_only_sees_their_own_payment_methods(): void
    {
        $user = $this->makeUserWithRole('attendee');
        $otherUser = $this->makeUserWithRole('attendee');

        $user->paymentMethods()->create([
            'gateway' => 'paystack',
            'type' => 'card',
            'gateway_payment_method_id' => 'ps_card_1',
            'brand' => 'Visa',
            'last_four' => '4242',
            'exp_month' => 12,
            'exp_year' => 2029,
            'is_default' => true,
        ]);

        $otherUser->paymentMethods()->create([
            'gateway' => 'flutterwave',
            'type' => 'card',
            'gateway_payment_method_id' => 'flw_card_2',
            'brand' => 'Mastercard',
            'last_four' => '1111',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/user/payment-methods')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.gateway', 'paystack')
            ->assertJsonPath('data.0.brand', 'Visa')
            ->assertJsonPath('data.0.lastFour', '4242')
            ->assertJsonPath('data.0.isDefault', true)
            ->assertJsonMissing(['details', 'paystack_customer_code', 'last_four_full'])
            ->assertJsonMissingPath('data.0.details');
    }

    public function test_transaction_history_excludes_authorization_and_gateway_secrets(): void
    {
        $user = $this->makeUserWithRole('attendee');

        Transaction::create([
            'user_id' => $user->id,
            'gateway' => 'paystack',
            'reference' => 'REF_TEST_0001',
            'authorization_code' => 'AUTH_SENSITIVE_TOKEN_0001',
            'authorization_type' => 'card',
            'amount' => 5000.00,
            'currency' => 'NGN',
            'status' => 'success',
            'customer_code' => 'CUS_SENSITIVE_0001',
            'gateway_response' => ['authorization_code' => 'AUTH_SENSITIVE_TOKEN_0001'],
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/user/transactions')
            ->assertOk();

        $response->assertJsonMissing([
            'authorizationCode',
            'authorizationType',
            'gatewayResponse',
            'customerCode',
            'lastError',
        ]);
        $response->assertJsonMissingPath('data.0.authorizationCode');
        $response->assertJsonMissingPath('data.0.authorizationType');
        $response->assertJsonMissingPath('data.0.gatewayResponse');
        $response->assertJsonPath('data.0.reference', 'REF_TEST_0001');
    }

    public function test_transaction_history_is_scoped_to_the_calling_user(): void
    {
        $user = $this->makeUserWithRole('attendee');
        $otherUser = $this->makeUserWithRole('attendee');

        Transaction::create([
            'user_id' => $user->id,
            'gateway' => 'paystack',
            'reference' => 'REF_OWNER',
            'amount' => 1000.00,
            'currency' => 'NGN',
            'status' => 'success',
        ]);

        Transaction::create([
            'user_id' => $otherUser->id,
            'gateway' => 'flutterwave',
            'reference' => 'REF_OTHER',
            'amount' => 2000.00,
            'currency' => 'NGN',
            'status' => 'success',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/user/transactions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference', 'REF_OWNER');
    }
}