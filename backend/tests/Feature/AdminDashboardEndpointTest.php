<?php

namespace Tests\Feature;

use App\Features\Fraud\Models\FraudEvent;
use App\Features\Payouts\Models\Payout;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AdminDashboardEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $regularUser;
    private string $adminToken;
    private string $regularToken;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        RateLimiter::clear('admin-dashboard');
        RateLimiter::clear('admin-activity-feed');
        RateLimiter::clear('admin-alerts');

        $adminRole = Role::factory()->create(['name' => 'admin']);
        $regularRole = Role::factory()->create(['name' => 'attendee']);

        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->adminToken = $this->adminUser->createToken('admin-token')->plainTextToken;

        $this->regularUser = User::factory()->create(['role_id' => $regularRole->id]);
        $this->regularToken = $this->regularUser->createToken('regular-token')->plainTextToken;
    }

    private function createEventAt(Carbon $timestamp): int
    {
        $organizer = Organizer::factory()->create();

        return DB::table("events")->insertGetId([
            "organizer_id" => (int) $organizer->id,
            "title" => "Test Event at " . $timestamp->toDateTimeString(),
            "description" => "Test",
            "start_datetime" => $timestamp->copy()->addDays(10),
            "end_datetime" => $timestamp->copy()->addDays(10)->addHours(4),
            "status" => "published",
            "capacity" => 100,
            "created_at" => $timestamp,
            "updated_at" => $timestamp,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // AUTH
    // ────────────────────────────────────────────────────────────────────────

    public function test_overview_returns_401_without_auth(): void
    {
        $this->getJson('/api/admin/dashboard/overview')->assertStatus(401);
    }

    public function test_overview_returns_403_for_non_admin(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->regularToken)
            ->getJson('/api/admin/dashboard/overview')
            ->assertStatus(403);
    }

    public function test_activity_feed_returns_401_without_auth(): void
    {
        $this->getJson('/api/admin/dashboard/activity-feed')->assertStatus(401);
    }

    public function test_activity_feed_returns_403_for_non_admin(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->regularToken)
            ->getJson('/api/admin/dashboard/activity-feed')
            ->assertStatus(403);
    }

    public function test_alerts_returns_401_without_auth(): void
    {
        $this->getJson('/api/admin/dashboard/alerts')->assertStatus(401);
    }

    public function test_alerts_returns_403_for_non_admin(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->regularToken)
            ->getJson('/api/admin/dashboard/alerts')
            ->assertStatus(403);
    }

    // ────────────────────────────────────────────────────────────────────────
    // OVERVIEW
    // ────────────────────────────────────────────────────────────────────────

    public function test_overview_returns_metrics_with_period(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/overview?period=7d');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('period', '7d')
            ->assertJsonStructure([
                'metrics' => [
                    'revenue',
                    'eventsCreated',
                    'usersRegistered',
                    'ticketsSold',
                    'completedPayouts',
                    'failedPayouts',
                    'failedPayments',
                ],
                'trends' => [
                    'revenue',
                    'ticketsSold',
                    'eventsCreated',
                    'usersRegistered',
                    'completedPayouts',
                ],
            ]);
    }

    public function test_overview_default_period_is_30d(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/overview');

        $response->assertStatus(200)
            ->assertJsonPath('period', '30d');
    }

    public function test_overview_accepts_all_period_values(): void
    {
        foreach (['24h', '7d', '30d', 'all_time'] as $period) {
            $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
                ->getJson('/api/admin/dashboard/overview?period=' . $period);

            $response->assertStatus(200)
                ->assertJsonPath('period', $period);
        }
    }

    public function test_overview_rejects_invalid_period(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/overview?period=invalid')
            ->assertStatus(422);
    }

    public function test_overview_trends_have_valid_directions(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/overview?period=7d');

        $response->assertStatus(200);
        foreach (['revenue', 'ticketsSold', 'eventsCreated', 'usersRegistered', 'completedPayouts'] as $key) {
            $this->assertContains(
                $response->json("trends.{$key}.direction"),
                ['up', 'down', 'flat']
            );
        }
    }

    public function test_overview_trend_is_up_when_current_exceeds_previous(): void
    {
        $now = Carbon::now();
        $currentPeriodStart = $now->copy()->subDays(7);

        Event::factory()->count(3)->create([
            'created_at' => $currentPeriodStart->copy()->addDays(1),
        ]);
        Event::factory()->count(1)->create([
            'created_at' => $currentPeriodStart->copy()->subDays(1),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/overview?period=7d');

        $response->assertStatus(200);
        $this->assertEquals('up', $response->json('trends.eventsCreated.direction'));
    }

    public function test_overview_trend_is_down_when_current_below_previous(): void
    {
        $now = Carbon::now();
        $currentPeriodStart = $now->copy()->subDays(7);

        $this->createEventAt($currentPeriodStart->copy()->addDays(1));
        $this->createEventAt($currentPeriodStart->copy()->subDays(1));
        $this->createEventAt($currentPeriodStart->copy()->subDays(2));
        $this->createEventAt($currentPeriodStart->copy()->subDays(3));
        $this->createEventAt($currentPeriodStart->copy()->subDays(4));
        $this->createEventAt($currentPeriodStart->copy()->subDays(5));

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/overview?period=7d');

        $response->assertStatus(200);
        $this->assertEquals('down', $response->json('trends.eventsCreated.direction'));
    }

    public function test_overview_trend_is_flat_when_no_data(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/overview?period=7d');

        $response->assertStatus(200);
        $this->assertEquals('flat', $response->json('trends.revenue.direction'));
        $this->assertEquals('flat', $response->json('trends.ticketsSold.direction'));
    }

    public function test_overview_empty_data_returns_zero_metrics(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/overview?period=24h');

        $response->assertStatus(200);
        $this->assertEquals(0.0, $response->json('metrics.revenue'));
        $this->assertEquals(0, $response->json('metrics.ticketsSold'));
        $this->assertEquals(0, $response->json('metrics.eventsCreated'));
    }

    // ────────────────────────────────────────────────────────────────────────
    // ACTIVITY FEED
    // ────────────────────────────────────────────────────────────────────────

    public function test_activity_feed_returns_paginated_activities(): void
    {
        for ($i = 0; $i < 15; $i++) {
            AuditLog::create([
                'user_id' => $this->adminUser->id,
                'target_type' => 'dashboard',
                'target_id' => 'test',
                'action' => 'event_created',
                'status' => 'success',
                'created_at' => Carbon::now()->subMinutes($i),
            ]);
        }

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/activity-feed?limit=5&offset=0')
            ->assertStatus(200);

        $this->assertTrue($response->json('success'));
        $this->assertEquals(15, $response->json('pagination.total'));
        $this->assertEquals(5, $response->json('pagination.limit'));
        $this->assertEquals(0, $response->json('pagination.offset'));
        $this->assertEquals(5, count($response->json('data')));
    }

    public function test_activity_feed_pagination_offset_works(): void
    {
        for ($i = 0; $i < 10; $i++) {
            AuditLog::create([
                'user_id' => $this->adminUser->id,
                'target_type' => 'dashboard',
                'target_id' => 'test',
                'action' => 'user_login',
                'status' => 'success',
                'created_at' => Carbon::now()->subMinutes($i),
            ]);
        }

        $page1 = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/activity-feed?limit=3&offset=0')
            ->assertStatus(200);

        $page2 = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/activity-feed?limit=3&offset=3')
            ->assertStatus(200);

        $this->assertEquals(3, count($page1->json('data')));
        $this->assertEquals(3, count($page2->json('data')));
        $this->assertNotEquals(
            $page1->json('data.0.id'),
            $page2->json('data.0.id')
        );
    }

    public function test_activity_feed_descriptions_are_human_readable(): void
    {
        AuditLog::create([
            'user_id' => $this->adminUser->id,
            'target_type' => 'dashboard',
            'target_id' => 'test',
            'action' => 'event_created',
            'status' => 'success',
            'created_at' => Carbon::now(),
        ]);

        AuditLog::create([
            'user_id' => $this->adminUser->id,
            'target_type' => 'dashboard',
            'target_id' => 'test',
            'action' => 'payment_refunded',
            'status' => 'success',
            'created_at' => Carbon::now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/activity-feed?limit=10')
            ->assertStatus(200);

        $descriptions = array_column($response->json('data'), 'description');
        $this->assertContains('Event Created', $descriptions);
        $this->assertContains('Payment Refunded', $descriptions);
    }

    public function test_activity_feed_filters_by_action(): void
    {
        AuditLog::create([
            'user_id' => $this->adminUser->id,
            'target_type' => 'dashboard',
            'target_id' => 'test',
            'action' => 'event_created',
            'status' => 'success',
            'created_at' => Carbon::now(),
        ]);
        AuditLog::create([
            'user_id' => $this->adminUser->id,
            'target_type' => 'dashboard',
            'target_id' => 'test',
            'action' => 'user_login',
            'status' => 'success',
            'created_at' => Carbon::now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/activity-feed?action=event_created')
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('event_created', $response->json('data.0.action'));
    }

    public function test_activity_feed_filters_by_status(): void
    {
        AuditLog::create([
            'user_id' => $this->adminUser->id,
            'target_type' => 'dashboard',
            'target_id' => 'test',
            'action' => 'event_created',
            'status' => 'success',
            'created_at' => Carbon::now(),
        ]);
        AuditLog::create([
            'user_id' => $this->adminUser->id,
            'target_type' => 'dashboard',
            'target_id' => 'test',
            'action' => 'payment_failed',
            'status' => 'failure',
            'error_message' => 'Gateway timeout',
            'created_at' => Carbon::now(),
        ]);

        $failures = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/activity-feed?status=failure')
            ->assertStatus(200);

        $this->assertCount(1, $failures->json('data'));
        $this->assertSame('failure', $failures->json('data.0.status'));
    }

    public function test_activity_feed_sorted_by_timestamp_desc(): void
    {
        AuditLog::create([
            'user_id' => $this->adminUser->id,
            'target_type' => 'dashboard',
            'target_id' => 'test',
            'action' => 'old_event',
            'status' => 'success',
            'created_at' => Carbon::now()->subHours(2),
        ]);
        AuditLog::create([
            'user_id' => $this->adminUser->id,
            'target_type' => 'dashboard',
            'target_id' => 'test',
            'action' => 'new_event',
            'status' => 'success',
            'created_at' => Carbon::now()->subHours(1),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/activity-feed?limit=10')
            ->assertStatus(200);

        $this->assertSame('new_event', $response->json('data.0.action'));
        $this->assertSame('old_event', $response->json('data.1.action'));
    }

    public function test_activity_feed_limit_capped_at_100(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/activity-feed?limit=101')
            ->assertStatus(422);
    }

    // ────────────────────────────────────────────────────────────────────────
    // ALERTS
    // ────────────────────────────────────────────────────────────────────────

    public function test_alerts_returns_aggregated_alerts(): void
    {
        FraudEvent::factory()->create([
            'status' => 'flagged',
            'risk_level' => 'high',
            'risk_score' => 95.0,
            'fraud_type' => 'duplicate_ticket_attempt',
            'created_at' => Carbon::now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/alerts')
            ->assertStatus(200);

        $response->assertJsonPath('success', true);
        $this->assertGreaterThan(0, $response->json('pagination.total'));
    }

    public function test_alerts_filters_by_severity_critical(): void
    {
        FraudEvent::factory()->create([
            'status' => 'flagged',
            'risk_level' => 'high',
            'risk_score' => 95.0,
            'fraud_type' => 'duplicate_ticket_attempt',
            'created_at' => Carbon::now(),
        ]);
        FraudEvent::factory()->create([
            'status' => 'flagged',
            'risk_level' => 'low',
            'risk_score' => 10.0,
            'fraud_type' => 'velocity_check_failed',
            'created_at' => Carbon::now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/alerts?severity=critical')
            ->assertStatus(200);

        $items = $response->json('data');
        $this->assertGreaterThanOrEqual(1, count($items));
        foreach ($items as $item) {
            $this->assertSame('critical', $item['severity']);
        }
    }

    public function test_alerts_filters_by_severity_warning(): void
    {
        FraudEvent::factory()->create([
            'status' => 'flagged',
            'risk_level' => 'medium',
            'risk_score' => 50.0,
            'fraud_type' => 'geolocation_anomaly',
            'created_at' => Carbon::now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/alerts?severity=warning')
            ->assertStatus(200);

        $items = $response->json('data');
        foreach ($items as $item) {
            $this->assertSame('warning', $item['severity']);
        }
    }

    public function test_alerts_filters_by_severity_info(): void
    {
        FraudEvent::factory()->create([
            'status' => 'reviewed',
            'risk_level' => 'low',
            'risk_score' => 5.0,
            'fraud_type' => 'device_fingerprint_mismatch',
            'created_at' => Carbon::now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/alerts?severity=info')
            ->assertStatus(200);

        $items = $response->json('data');
        foreach ($items as $item) {
            $this->assertSame('info', $item['severity']);
        }
    }

    public function test_alerts_rejects_invalid_severity(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/alerts?severity=bogus')
            ->assertStatus(422);
    }

    public function test_alerts_includes_payout_failures_as_critical(): void
    {
        $organizer = Organizer::factory()->create();
        Payout::factory()->create([
            'organizer_id' => $organizer->id,
            'status' => Payout::STATUS_FAILED,
            'failure_reason' => 'Bank rejected transfer',
            'created_at' => Carbon::now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/alerts')
            ->assertStatus(200);

        $items = $response->json('data');
        $types = array_column($items, 'type');
        $this->assertContains('payout_failure', $types);
    }

    public function test_alerts_includes_audit_failures(): void
    {
        AuditLog::create([
            'user_id' => $this->adminUser->id,
            'target_type' => 'dashboard',
            'target_id' => 'test',
            'action' => 'payment_processed',
            'status' => 'failure',
            'error_message' => 'Gateway timeout',
            'error_code' => 'ETIMEDOUT',
            'created_at' => Carbon::now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/alerts')
            ->assertStatus(200);

        $items = $response->json('data');
        $types = array_column($items, 'type');
        $this->assertContains('audit_failure', $types);
    }

    public function test_alerts_sorted_by_severity_then_timestamp(): void
    {
        FraudEvent::factory()->create([
            'status' => 'flagged',
            'risk_level' => 'low',
            'risk_score' => 5.0,
            'fraud_type' => 'device_fingerprint_mismatch',
            'created_at' => Carbon::now()->subMinutes(10),
        ]);
        FraudEvent::factory()->create([
            'status' => 'auto_blocked',
            'risk_level' => 'high',
            'risk_score' => 95.0,
            'fraud_type' => 'card_testing',
            'created_at' => Carbon::now()->subMinutes(5),
        ]);
        FraudEvent::factory()->create([
            'status' => 'flagged',
            'risk_level' => 'medium',
            'risk_score' => 40.0,
            'fraud_type' => 'geolocation_anomaly',
            'created_at' => Carbon::now()->subMinutes(1),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/alerts')
            ->assertStatus(200);

        $items = $response->json('data');
        $this->assertGreaterThanOrEqual(3, count($items));
        $this->assertSame('critical', $items[0]['severity']);
    }

    public function test_alerts_pagination_works(): void
    {
        for ($i = 0; $i < 10; $i++) {
            FraudEvent::factory()->create([
                'status' => 'flagged',
                'risk_level' => 'medium',
                'risk_score' => 40.0 + $i,
                'fraud_type' => 'test_alert_' . $i,
                'created_at' => Carbon::now()->subMinutes($i),
            ]);
        }

        $page1 = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/alerts?limit=3&offset=0')
            ->assertStatus(200);

        $page2 = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/alerts?limit=3&offset=3')
            ->assertStatus(200);

        $this->assertEquals(3, count($page1->json('data')));
        $this->assertEquals(3, count($page2->json('data')));
    }

    public function test_alerts_limit_capped_at_100(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/alerts?limit=101')
            ->assertStatus(422);
    }

    public function test_alerts_no_data_returns_empty(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/alerts')
            ->assertStatus(200);

        $response->assertJsonPath('data', []);
        $response->assertJsonPath('pagination.total', 0);
    }

    // ────────────────────────────────────────────────────────────────────────
    // RATE LIMITING
    // ────────────────────────────────────────────────────────────────────────

    public function test_overview_rate_limited_after_20_requests(): void
    {
        Cache::flush();
        RateLimiter::clear('admin-dashboard');

        $headers = ['Authorization' => 'Bearer ' . $this->adminToken];

        for ($i = 0; $i < 20; $i++) {
            $this->getJson('/api/admin/dashboard/overview', $headers)->assertStatus(200);
        }

        $this->getJson('/api/admin/dashboard/overview', $headers)->assertStatus(429);
    }

    public function test_activity_feed_rate_limited_after_20_requests(): void
    {
        Cache::flush();
        RateLimiter::clear('admin-activity-feed');

        $headers = ['Authorization' => 'Bearer ' . $this->adminToken];

        for ($i = 0; $i < 20; $i++) {
            $this->getJson('/api/admin/dashboard/activity-feed', $headers)->assertStatus(200);
        }

        $this->getJson('/api/admin/dashboard/activity-feed', $headers)->assertStatus(429);
    }

    public function test_alerts_rate_limited_after_20_requests(): void
    {
        Cache::flush();
        RateLimiter::clear('admin-alerts');

        $headers = ['Authorization' => 'Bearer ' . $this->adminToken];

        for ($i = 0; $i < 20; $i++) {
            $this->getJson('/api/admin/dashboard/alerts', $headers)->assertStatus(200);
        }

        $this->getJson('/api/admin/dashboard/alerts', $headers)->assertStatus(429);
    }

    // ────────────────────────────────────────────────────────────────────────
    // AUDIT LOGGING
    // ────────────────────────────────────────────────────────────────────────

    public function test_overview_creates_audit_log(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/overview?period=7d')
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->adminUser->id,
            'action' => 'admin.dashboard.overview',
        ]);
    }

    public function test_activity_feed_creates_audit_log(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/activity-feed')
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->adminUser->id,
            'action' => 'admin.dashboard.activity_feed',
        ]);
    }

    public function test_alerts_creates_audit_log(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/alerts')
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->adminUser->id,
            'action' => 'admin.dashboard.alerts',
        ]);
    }
}
