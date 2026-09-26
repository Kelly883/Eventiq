<?php

namespace Tests\Feature;

use App\Features\Payouts\Models\Payout;
use App\Models\Organizer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PayoutListEndpointTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The rate limiter stores attempts in the cache (array in tests). Flush
     * before every test so limiter state never leaks between tests.
     */
         /**
     * Flush the cache (and the per-key rate-limiter state) before every test so
     * throttle counters from one test never leak into another.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        RateLimiter::clear('payouts-list');
    }

            private function makeOrganizerUser(): Organizer
    {
        $role = Role::firstOrCreate(['name' => 'organizer'], [
            'description' => 'Event organiser',
            'isSystemRole' => true,
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        return Organizer::factory()->create(['user_id' => $user->id]);
    }

    private function makeAdminUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin'], [
            'description' => 'Full administrative access',
            'isSystemRole' => true,
        ]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    /** A plain user with no organiser profile and no admin role. */
    private function makeRegularUser(): User
    {
        return User::factory()->create();
    }

            /** Builds a Bearer header for a User, or for an Organizer (resolves its user). */
    private function bearer($userOrOrganizer): array
    {
        $user = $userOrOrganizer instanceof User ? $userOrOrganizer : $userOrOrganizer->user;

        return ['Authorization' => 'Bearer ' . $user->createToken('api-token')->plainTextToken];
    }

    /**
     * Authenticate as the given user/organiser for the next request WITHOUT a
     * real bearer token. This is deterministic across tests (Sanctum's
     * createToken relies on personal_access_tokens auto-increment ids, which
     * SQLite rowid-reuses under RefreshDatabase and can collide when several
     * users are authenticated within one test). actingAs sets the user on the
     * auth guard directly so the controller's scoping code is exercised
     * faithfully while avoiding token-table resolution noise.
     */
    private function actingAsHeader($userOrOrganizer): array
    {
        $user = $userOrOrganizer instanceof User ? $userOrOrganizer : $userOrOrganizer->user;
        Sanctum::actingAs($user);

        return [];
    }

    private function makePayout(Organizer $organizer, array $overrides = []): Payout
    {
        return Payout::factory()->create(array_merge([
            'organizer_id' => $organizer->id,
            'settlement_period_start_date' => now()->subDays(14),
            'settlement_period_end_date' => now()->subDays(7),
        ], $overrides));
    }

            public function test_list_returns_401_without_auth_token(): void
    {
        $this->getJson('/api/organizer/payouts/list')->assertStatus(401);
    }

    public function test_list_returns_401_with_invalid_token(): void
    {
        $this->getJson('/api/organizer/payouts/list', [
            'Authorization' => 'Bearer not-a-real-token',
        ])->assertStatus(401);
    }

    public function test_list_returns_403_for_non_organizer(): void
    {
        $regular = $this->makeRegularUser();

        $this->getJson('/api/organizer/payouts/list', $this->bearer($regular))
            ->assertStatus(403);
    }

    public function test_list_403_takes_precedence_over_400_for_non_organizer_with_bad_params(): void
    {
        $regular = $this->makeRegularUser();

        $this->getJson('/api/organizer/payouts/list?status=bogus&limit=999', $this->bearer($regular))
            ->assertStatus(403);
    }

    public function test_list_returns_400_for_invalid_status(): void
    {
        $organizer = $this->makeOrganizerUser();

        $this->getJson('/api/organizer/payouts/list?status=bogus', $this->bearer($organizer))
            ->assertStatus(400);
    }

    public function test_list_returns_400_for_invalid_date_format(): void
    {
        $organizer = $this->makeOrganizerUser();

        $this->getJson('/api/organizer/payouts/list?start_date=not-a-date', $this->bearer($organizer))
            ->assertStatus(400);

        $this->getJson('/api/organizer/payouts/list?start_date=01/01/2025', $this->bearer($organizer))
            ->assertStatus(400);
    }

    public function test_list_returns_400_for_inverted_date_range(): void
    {
        $organizer = $this->makeOrganizerUser();

        $this->getJson('/api/organizer/payouts/list?start_date=2025-06-20&end_date=2025-06-10', $this->bearer($organizer))
            ->assertStatus(400);
    }

    public function test_list_returns_400_when_limit_exceeds_max(): void
    {
        $organizer = $this->makeOrganizerUser();

        $this->getJson('/api/organizer/payouts/list?limit=101', $this->bearer($organizer))
            ->assertStatus(400);

        $this->getJson('/api/organizer/payouts/list?limit=150', $this->bearer($organizer))
            ->assertStatus(400);

        // per_page alias is capped too, and a valid limit must be accepted.
        $this->getJson('/api/organizer/payouts/list?per_page=100', $this->bearer($organizer))
            ->assertStatus(200);
    }

    public function test_list_returns_400_for_invalid_sort_params(): void
    {
        $organizer = $this->makeOrganizerUser();

        $this->getJson('/api/organizer/payouts/list?sort_by=evil', $this->bearer($organizer))
            ->assertStatus(400);

        $this->getJson('/api/organizer/payouts/list?sort_dir=sideways', $this->bearer($organizer))
            ->assertStatus(400);
    }

    public function test_list_returns_200_with_paginated_payouts_for_organizer(): void
    {
        $organizer = $this->makeOrganizerUser();
        $this->makePayout($organizer);
        $this->makePayout($organizer);
        $this->makePayout($organizer);

        $response = $this->getJson('/api/organizer/payouts/list', $this->bearer($organizer))
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);

        $response->assertJsonPath('meta.total', 3);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_list_filters_by_status(): void
    {
        $organizer = $this->makeOrganizerUser();
        $this->makePayout($organizer, ['status' => Payout::STATUS_COMPLETED, 'completed_at' => now()]);
        $this->makePayout($organizer, ['status' => Payout::STATUS_COMPLETED, 'completed_at' => now()]);
        $this->makePayout($organizer, ['status' => Payout::STATUS_PENDING, 'completed_at' => null]);
        $this->makePayout($organizer, ['status' => Payout::STATUS_FAILED, 'completed_at' => null]);

        $completed = $this->getJson('/api/organizer/payouts/list?status=completed', $this->bearer($organizer))
            ->assertStatus(200);
        $this->assertCount(2, $completed->json('data'));
        $this->assertSame(Payout::STATUS_COMPLETED, $completed->json('data.0.status'));
        $this->assertSame(Payout::STATUS_COMPLETED, $completed->json('data.1.status'));

        $pending = $this->getJson('/api/organizer/payouts/list?status=pending', $this->bearer($organizer))
            ->assertStatus(200);
        $this->assertCount(1, $pending->json('data'));
        $this->assertSame(Payout::STATUS_PENDING, $pending->json('data.0.status'));
    }

    public function test_list_filters_by_date_range(): void
    {
        $organizer = $this->makeOrganizerUser();

        $p1 = $this->makePayout($organizer, ['created_at' => '2025-06-10 10:00:00']);
        $p2 = $this->makePayout($organizer, ['created_at' => '2025-06-15 10:00:00']);
        $p3 = $this->makePayout($organizer, ['created_at' => '2025-06-20 10:00:00']);

        // Range [Jun 12, Jun 18] should only return $p2.
        $response = $this->getJson(
            '/api/organizer/payouts/list?start_date=2025-06-12&end_date=2025-06-18',
            $this->bearer($organizer)
        )->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
        $this->assertSame($p2->id, $response->json('data.0.id'));
    }

    public function test_list_sorts_by_created_at(): void
    {
        $organizer = $this->makeOrganizerUser();

        $old = $this->makePayout($organizer, ['created_at' => '2025-01-01 08:00:00', 'payout_amount' => 100]);
        $mid = $this->makePayout($organizer, ['created_at' => '2025-02-01 08:00:00', 'payout_amount' => 200]);
        $new = $this->makePayout($organizer, ['created_at' => '2025-03-01 08:00:00', 'payout_amount' => 300]);

        $asc = $this->getJson(
            '/api/organizer/payouts/list?sort_by=createdAt&sort_dir=asc',
            $this->bearer($organizer)
        )->assertStatus(200);
        $this->assertSame($old->id, $asc->json('data.0.id'));

        // desc (the default)
        $desc = $this->getJson('/api/organizer/payouts/list', $this->bearer($organizer))
            ->assertStatus(200);
        $this->assertSame($new->id, $desc->json('data.0.id'));
    }

    public function test_list_sorts_by_payout_amount(): void
    {
        $organizer = $this->makeOrganizerUser();

        $low = $this->makePayout($organizer, ['payout_amount' => 100, 'created_at' => '2025-01-01 08:00:00']);
        $high = $this->makePayout($organizer, ['payout_amount' => 9999, 'created_at' => '2025-01-02 08:00:00']);
        $mid = $this->makePayout($organizer, ['payout_amount' => 500, 'created_at' => '2025-01-03 08:00:00']);

        $desc = $this->getJson(
            '/api/organizer/payouts/list?sort_by=payoutAmount&sort_dir=desc',
            $this->bearer($organizer)
        )->assertStatus(200);
        $this->assertSame($high->id, $desc->json('data.0.id'));
        $this->assertGreaterThan((float) $mid->payout_amount, (float) $desc->json('data.0.payoutAmount'));

        $asc = $this->getJson(
            '/api/organizer/payouts/list?sort_by=payoutAmount&sort_dir=asc',
            $this->bearer($organizer)
        )->assertStatus(200);
        $this->assertSame($low->id, $asc->json('data.0.id'));
    }

    public function test_list_pagination_first_and_second_page(): void
    {
        $organizer = $this->makeOrganizerUser();

        for ($i = 0; $i < 100; $i++) {
            $this->makePayout($organizer, [
                'created_at' => now()->subSeconds(100 - $i),
                'payout_amount' => $i + 1,
            ]);
        }

        $page1 = $this->getJson('/api/organizer/payouts/list?limit=50&page=1', $this->bearer($organizer))
            ->assertStatus(200);
        $this->assertCount(50, $page1->json('data'));
        $page1->assertJsonPath('meta.total', 100);
        $page1->assertJsonPath('meta.last_page', 2);
        $page1->assertJsonPath('meta.per_page', 50);
        $page1->assertJsonPath('meta.current_page', 1);

        $page2 = $this->getJson('/api/organizer/payouts/list?limit=50&page=2', $this->bearer($organizer))
            ->assertStatus(200);
        $this->assertCount(50, $page2->json('data'));
        $page2->assertJsonPath('meta.current_page', 2);

        $ids1 = array_column($page1->json('data'), 'id');
        $ids2 = array_column($page2->json('data'), 'id');
        $this->assertSame(100, count(array_unique(array_merge($ids1, $ids2))));
    }

    public function test_list_default_page_size_is_50(): void
    {
        $organizer = $this->makeOrganizerUser();

        for ($i = 0; $i < 60; $i++) {
            $this->makePayout($organizer, ['created_at' => now()->subSeconds(60 - $i)]);
        }

        $response = $this->getJson('/api/organizer/payouts/list', $this->bearer($organizer))
            ->assertStatus(200);

        $this->assertCount(50, $response->json('data'));
        $response->assertJsonPath('meta.per_page', 50);
        $response->assertJsonPath('meta.current_page', 1);
    }

    public function test_list_rate_limited_after_20_requests_per_minute(): void
    {
        $organizer = $this->makeOrganizerUser();
        Cache::flush();
        RateLimiter::clear('payouts-list');

        $headers = $this->bearer($organizer);

        for ($i = 1; $i <= 20; $i++) {
            $this->getJson('/api/organizer/payouts/list', $headers)->assertStatus(200);
        }

        $this->getJson('/api/organizer/payouts/list', $headers)->assertStatus(429);
    }

                public function test_organizer_only_sees_own_payouts(): void
    {
        $organizerA = $this->makeOrganizerUser();
        $organizerB = $this->makeOrganizerUser(); // a second, unrelated organiser

        // A's payouts
        $this->makePayout($organizerA);
        $this->makePayout($organizerA);
        // B's payouts
        $this->makePayout($organizerB);
        $this->makePayout($organizerB);
        $this->makePayout($organizerB);

        // as A → only A's 2 payouts
        $asA = $this->getJson('/api/organizer/payouts/list', $this->actingAsHeader($organizerA))
            ->assertStatus(200);
        $this->assertCount(2, $asA->json('data'));
        $this->assertContains($organizerA->id, array_column($asA->json('data'), 'organizerId'));
        $this->assertNotContains($organizerB->id, array_column($asA->json('data'), 'organizerId'));

        // as B → only B's 3 payouts (proves no cross-organiser leakage)
        $asB = $this->getJson('/api/organizer/payouts/list', $this->actingAsHeader($organizerB))
            ->assertStatus(200);
        $this->assertCount(3, $asB->json('data'));
        $this->assertContains($organizerB->id, array_column($asB->json('data'), 'organizerId'));
        $this->assertNotContains($organizerA->id, array_column($asB->json('data'), 'organizerId'));

        // Cross-check the raw DB matches the scoped endpoint result.
        $this->assertSame(2, \App\Features\Payouts\Models\Payout::where('organizer_id', $organizerA->id)->count());
        $this->assertSame(3, \App\Features\Payouts\Models\Payout::where('organizer_id', $organizerB->id)->count());
    }

    public function test_admin_sees_all_payouts(): void
    {
        $organizer = $this->makeOrganizerUser();
        $this->makePayout($organizer);
        $this->makePayout($organizer);

        $admin = $this->makeAdminUser();

        $response = $this->getJson('/api/organizer/payouts/list', $this->bearer($admin))
            ->assertStatus(200);

        $response->assertJsonPath('meta.total', 2);
    }

    public function test_admin_sees_all_organizers_payouts_with_real_bearer_token(): void
    {
        $organizerA = $this->makeOrganizerUser();
        $organizerB = $this->makeOrganizerUser();
        $organizerC = $this->makeOrganizerUser();

        $this->makePayout($organizerA, ['payout_amount' => 1000]);
        $this->makePayout($organizerA, ['payout_amount' => 2000]);
        $this->makePayout($organizerB, ['payout_amount' => 3000]);
        $this->makePayout($organizerC, ['payout_amount' => 4000]);
        $this->makePayout($organizerC, ['payout_amount' => 5000]);
        $this->makePayout($organizerC, ['payout_amount' => 6000]);

        $admin = $this->makeAdminUser();

        $response = $this->getJson('/api/organizer/payouts/list', $this->bearer($admin))
            ->assertStatus(200);

        $response->assertJsonPath('meta.total', 6);
        $this->assertCount(6, $response->json('data'));

        $ids = array_column($response->json('data'), 'id');
        $this->assertSame(6, count(array_unique($ids)));

        $organizerIds = array_unique(array_column($response->json('data'), 'organizerId'));
        $this->assertContains($organizerA->id, $organizerIds);
        $this->assertContains($organizerB->id, $organizerIds);
        $this->assertContains($organizerC->id, $organizerIds);
    }

    public function test_list_response_includes_required_fields(): void
    {
        $organizer = $this->makeOrganizerUser();
        $payout = $this->makePayout($organizer, [
            'gross_revenue' => 95000.00,
            'refunds_deducted' => 5000.00,
            'net_revenue' => 90000.00,
            'platform_commission_amount' => 9500.00,
            'payout_amount' => 80500.00,
            'currency' => 'USD',
            'payout_method' => 'bank_transfer',
            'status' => Payout::STATUS_COMPLETED,
        ]);

        $response = $this->getJson('/api/organizer/payouts/list', $this->bearer($organizer))
            ->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'organizerId',
                    'settlementPeriodStartDate',
                    'settlementPeriodEndDate',
                    'grossRevenue',
                    'refundsDeducted',
                    'netRevenue',
                    'platformCommissionPercentage',
                    'platformCommissionAmount',
                    'payoutAmount',
                    'currency',
                    'payoutMethod',
                    'status',
                    'completedAt',
                    'createdAt',
                    'updatedAt',
                ],
            ],
        ]);

        $first = $response->json('data.0');
        $this->assertSame($payout->id, $first['id']);
        $this->assertSame($organizer->id, $first['organizerId']);
        $this->assertSame('completed', $first['status']);
        $this->assertSame('bank_transfer', $first['payoutMethod']);
        $this->assertEquals(80500.00, $first['payoutAmount']);
        $this->assertEquals(9500.00, $first['platformCommissionAmount']);
        $this->assertEquals(5000.00, $first['refundsDeducted']);
        $this->assertNotNull($first['settlementPeriodStartDate']);
        $this->assertNotNull($first['settlementPeriodEndDate']);
        $this->assertNotNull($first['completedAt']);
    }

    public function test_list_returns_empty_for_organizer_with_no_payouts(): void
    {
        $organizer = $this->makeOrganizerUser();

        $response = $this->getJson('/api/organizer/payouts/list', $this->bearer($organizer))
            ->assertStatus(200);

        $this->assertSame([], $response->json('data'));
        $response->assertJsonPath('meta.total', 0);
    }

    public function test_list_limit_capped_at_100(): void
    {
        $organizer = $this->makeOrganizerUser();

        for ($i = 0; $i < 150; $i++) {
            $this->makePayout($organizer, ['created_at' => now()->subSeconds(150 - $i)]);
        }

        $cap = $this->getJson('/api/organizer/payouts/list?limit=100', $this->bearer($organizer))
            ->assertStatus(200);
        $this->assertCount(100, $cap->json('data'));
        $cap->assertJsonPath('meta.per_page', 100);
        $cap->assertJsonPath('meta.total', 150);
        $cap->assertJsonPath('meta.last_page', 2);
    }

    public function test_backward_compatible_alias_payouts_returns_list(): void
    {
        $organizer = $this->makeOrganizerUser();
        $this->makePayout($organizer);

        $this->getJson('/api/organizer/payouts', $this->bearer($organizer))
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_full_flow_status_filter_date_filter_and_rate_limit(): void
    {
        $organizer = $this->makeOrganizerUser();

        $completed = $this->makePayout($organizer, [
            'status' => Payout::STATUS_COMPLETED,
            'completed_at' => now(),
            'created_at' => '2025-06-15 10:00:00',
            'payout_amount' => 5000,
        ]);
        $pending = $this->makePayout($organizer, [
            'status' => Payout::STATUS_PENDING,
            'completed_at' => null,
            'created_at' => '2025-06-16 10:00:00',
            'payout_amount' => 3000,
        ]);

                // Another organiser's payout must never leak to the first organiser.
        $otherOrganizer = $this->makeOrganizerUser();
        $this->makePayout($otherOrganizer, [
            'status' => Payout::STATUS_COMPLETED,
            'created_at' => '2025-06-15 10:00:00',
        ]);

        $token = $this->bearer($organizer);

        // 1) Authenticated organiser sees only their own payouts (2 of them).
        $all = $this->getJson('/api/organizer/payouts/list', $token)->assertStatus(200);
        $this->assertCount(2, $all->json('data'));
        $all->assertJsonPath('meta.total', 2);

        // 2) Filter by status=completed -> only the completed one.
        $onlyCompleted = $this->getJson('/api/organizer/payouts/list?status=completed', $token)
            ->assertStatus(200);
        $this->assertCount(1, $onlyCompleted->json('data'));
        $this->assertSame($completed->id, $onlyCompleted->json('data.0.id'));
        $this->assertSame('completed', $onlyCompleted->json('data.0.status'));

        // 3) Filter by date range.
        $inRange = $this->getJson(
            '/api/organizer/payouts/list?start_date=2025-06-14&end_date=2025-06-16',
            $token
        )->assertStatus(200);
        $this->assertCount(2, $inRange->json('data'));

        $outsideRange = $this->getJson(
            '/api/organizer/payouts/list?start_date=2025-06-17&end_date=2025-06-18',
            $token
        )->assertStatus(200);
        $this->assertCount(0, $outsideRange->json('data'));

        // 4) Spam the endpoint 21 times -> 429 on the 21st.
        Cache::flush();
        RateLimiter::clear('payouts-list');

        for ($i = 1; $i <= 20; $i++) {
            $this->getJson('/api/organizer/payouts/list', $token)->assertStatus(200);
        }
        $this->getJson('/api/organizer/payouts/list', $token)->assertStatus(429);
    }

    // ------------------------------------------------------------------
    // /payouts/summary endpoint coverage
    // (Previously this endpoint crashed because it summed a non-existent
    // `amount` column instead of `payout_amount`.)
    // ------------------------------------------------------------------

    public function test_summary_returns_401_without_auth_token(): void
    {
        $this->getJson('/api/organizer/payouts/summary')->assertStatus(401);
    }

    public function test_summary_returns_403_for_non_organizer(): void
    {
        $regular = $this->makeRegularUser();

        $this->getJson('/api/organizer/payouts/summary', $this->bearer($regular))
            ->assertStatus(403);
    }

    public function test_summary_returns_correct_totals_and_is_scoped_to_organizer(): void
    {
        $organizer = $this->makeOrganizerUser();

        $this->makePayout($organizer, ['status' => Payout::STATUS_COMPLETED, 'payout_amount' => 1000, 'completed_at' => now(), 'created_at' => '2025-06-15 10:00:00']);
        $this->makePayout($organizer, ['status' => Payout::STATUS_COMPLETED, 'payout_amount' => 2000, 'completed_at' => now(), 'created_at' => '2025-06-15 10:00:00']);
        $this->makePayout($organizer, ['status' => Payout::STATUS_PENDING, 'payout_amount' => 500, 'created_at' => '2025-06-16 10:00:00']);
        $this->makePayout($organizer, ['status' => Payout::STATUS_PROCESSING, 'payout_amount' => 800, 'created_at' => '2025-06-17 10:00:00']);

        // A second organiser's payouts must NOT leak into organizer A's summary.
        $otherOrganizer = $this->makeOrganizerUser();
        $this->makePayout($otherOrganizer, ['status' => Payout::STATUS_COMPLETED, 'payout_amount' => 99999, 'completed_at' => now()]);

        $response = $this->getJson('/api/organizer/payouts/summary', $this->bearer($organizer))
            ->assertStatus(200);

        // NOTE: assertEquals (loose) is used for the monetary sums because
        // SQLite's sum() returns an integer for whole-number results and
        // JSON round-trip strips a trailing .0, so assertJsonPath's strict
        // assertSame would fail on 3000 vs 3000.0.
        // completed (1000 + 2000)
        $this->assertEquals(3000.0, $response->json('total_processed'));
        // pending (500)
        $this->assertEquals(500.0, $response->json('total_pending'));
        // processing (800)
        $this->assertEquals(800.0, $response->json('total_processing'));
        // completed + pending + processing (1000 + 2000 + 500 + 800)
        $this->assertEquals(4300.0, $response->json('total_earned'));
        // next_payout should be the pending one with the smallest created_at (500).
        $this->assertEquals(500.0, $response->json('next_payout'));
        $this->assertNotNull($response->json('next_payout_date'));

        // Sanity: the other organiser's 99999 payout exists in the DB but was
        // NOT included in organizer A's totals (total_processed stays 3000,
        // not 102999). Also confirms the cross-organiser payout count.
        $this->assertSame(1, Payout::where('organizer_id', $otherOrganizer->id)->where('status', Payout::STATUS_COMPLETED)->count());
        $this->assertNotEquals(102999.0, $response->json('total_processed'));
    }

    public function test_summary_admin_sees_global_totals(): void
    {
        $organizerA = $this->makeOrganizerUser();
        $organizerB = $this->makeOrganizerUser();

        $this->makePayout($organizerA, ['status' => Payout::STATUS_COMPLETED, 'payout_amount' => 1000, 'completed_at' => now()]);
        $this->makePayout($organizerB, ['status' => Payout::STATUS_COMPLETED, 'payout_amount' => 2000, 'completed_at' => now()]);

        $admin = $this->makeAdminUser();

        $response = $this->getJson('/api/organizer/payouts/summary', $this->bearer($admin))
            ->assertStatus(200);

        // Admin sees both organisers' completed payouts (1000 + 2000).
        $this->assertEquals(3000.0, $response->json('total_processed'));
        $this->assertEquals(3000.0, $response->json('total_earned'));
    }

    // ------------------------------------------------------------------
    // Date-range boundary edge cases (validates the sargable range query)
    // ------------------------------------------------------------------

    public function test_list_date_filter_end_date_is_inclusive_of_full_day(): void
    {
        $organizer = $this->makeOrganizerUser();

        // Created at 23:50 on the end-date → must be INCLUDED.
        $this->makePayout($organizer, ['created_at' => '2025-06-18 23:50:00']);
        // Created at 00:10 the day AFTER end-date → must be EXCLUDED.
        $this->makePayout($organizer, ['created_at' => '2025-06-19 00:10:00']);

        $response = $this->getJson(
            '/api/organizer/payouts/list?start_date=2025-06-01&end_date=2025-06-18',
            $this->bearer($organizer)
        )->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
        $response->assertJsonPath('meta.total', 1);
    }

    public function test_list_rejects_page_below_minimum(): void
    {
        $organizer = $this->makeOrganizerUser();

        $this->getJson('/api/organizer/payouts/list?page=0', $this->bearer($organizer))
            ->assertStatus(400);

        $this->getJson('/api/organizer/payouts/list?page=-1', $this->bearer($organizer))
            ->assertStatus(400);
    }

    public function test_list_limit_alias_per_page_wins_when_limit_omitted(): void
    {
        $organizer = $this->makeOrganizerUser();

        for ($i = 0; $i < 10; $i++) {
            $this->makePayout($organizer, ['created_at' => now()->subSeconds(10 - $i)]);
        }

        // per_page is the documented alias; 5 should return 5.
        $response = $this->getJson('/api/organizer/payouts/list?per_page=5', $this->bearer($organizer))
            ->assertStatus(200);

        $this->assertCount(5, $response->json('data'));
        $response->assertJsonPath('meta.per_page', 5);
        $response->assertJsonPath('meta.total', 10);
    }
}
