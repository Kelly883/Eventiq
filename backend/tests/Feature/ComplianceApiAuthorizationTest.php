<?php

namespace Tests\Feature;

use App\Features\Compliance\Enums\AuditLogAction;
use App\Features\Compliance\Enums\AuditLogStatus;
use App\Features\Compliance\Enums\ComplianceClassification;
use App\Features\Compliance\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ComplianceApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
                Cache::flush();
        RateLimiter::clear('compliance-list');
        RateLimiter::clear('compliance-export');
        RateLimiter::clear('compliance-bulk-tag');
        RateLimiter::clear('compliance-report-generate');
    }

    private function makeAdmin(): User
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrator', 'isSystemRole' => true]
        );
        $user = User::factory()->create();
        $user->roles()->attach($adminRole);
        return $user;
    }

    private function makeRegularUser(): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'organizer'],
            ['description' => 'Event Organizer']
        );
        $user = User::factory()->create();
        $user->roles()->attach($role);
        return $user;
    }

    private function makeAuditLog(array $overrides = []): AuditLog
    {
        return AuditLog::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'action' => 'user_login',
            'target_type' => 'user',
            'target_id' => null,
            'description' => 'Test audit log entry',
            'status' => 'success',
            'compliance_classification' => 'internal',
            'ip_address' => '192.168.1.100',
            'retention_date' => now()->addYears(7),
        ], $overrides));
    }

    // 1. GET /api/admin/compliance/audit-logs

    public function test_list_returns_401_without_auth_token(): void
    {
        $this->getJson('/api/admin/compliance/audit-logs')->assertStatus(401);
    }

    public function test_list_returns_403_for_non_admin(): void
    {
        $response = $this->actingAs($this->makeRegularUser(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs');

        $response->assertForbidden();
    }

    public function test_list_returns_200_with_paginated_data(): void
    {
        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs');

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_list_filters_by_action(): void
    {
        $this->makeAuditLog(['action' => 'user_login']);
        $this->makeAuditLog(['action' => 'user_logout']);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs?action=user_login');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('user_login', $response->json('data.0.action'));
    }

    public function test_list_filters_by_status(): void
    {
        $this->makeAuditLog(['status' => 'success']);
        $this->makeAuditLog(['status' => 'failure']);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs?status=failure');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('failure', $response->json('data.0.status'));
    }

    public function test_list_filters_by_target_type(): void
    {
        $this->makeAuditLog(['target_type' => 'user']);
        $this->makeAuditLog(['target_type' => 'event']);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs?targetType=event');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('event', $response->json('data.0.targetType'));
    }

    public function test_list_filters_by_classification(): void
    {
        $this->makeAuditLog(['compliance_classification' => 'internal']);
        $this->makeAuditLog(['compliance_classification' => 'restricted']);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs?classification=restricted');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('restricted', $response->json('data.0.complianceClassification'));
    }

    public function test_list_filters_by_date_range(): void
    {
        $this->makeAuditLog(['created_at' => now()->subDays(5)]);
        $this->makeAuditLog(['created_at' => now()]);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs?from=' . now()->subDays(3)->toDateString());

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    // 2. Sorting

    public function test_list_sorts_by_created_at_asc(): void
    {
        $this->makeAuditLog(['created_at' => now()->subDays(2)]);
        $this->makeAuditLog(['created_at' => now()->subDay()]);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs?sortBy=createdAt&sortOrder=asc');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(
            strtotime($response->json('data.0.createdAt') ?? 0),
            strtotime($response->json('data.1.createdAt') ?? 0)
        );
    }

    public function test_list_sorts_by_created_at_desc(): void
    {
        $this->makeAuditLog(['created_at' => now()->subDays(2)]);
        $this->makeAuditLog(['created_at' => now()->subDay()]);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs?sortBy=createdAt&sortOrder=desc');

        $response->assertOk();
        $this->assertLessThanOrEqual(
            strtotime($response->json('data.0.createdAt') ?? 0),
            strtotime($response->json('data.1.createdAt') ?? 0)
        );
    }

                public function test_list_sorts_by_action(): void
    {
        $this->makeAuditLog(['action' => 'event_created']);
        $this->makeAuditLog(['action' => 'event_approved']);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs?sortBy=action&sortOrder=asc');

                $response->assertOk();
        $this->assertEquals('event_approved', $response->json('data.0.action'));
        $this->assertEquals('event_created', $response->json('data.1.action'));
    }

    // 3. Summary

    public function test_summary_metrics_calculated_correctly(): void
    {
        $this->makeAuditLog(['status' => 'success']);
        $this->makeAuditLog(['status' => 'failure']);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs/summary');

        $response->assertOk();
        $this->assertArrayHasKey('totalEvents', $response->json());
        $this->assertArrayHasKey('successRate', $response->json());
        $this->assertArrayHasKey('failedCount', $response->json());
    }

    public function test_summary_with_no_logs_returns_zeros(): void
    {
        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs/summary');

        $response->assertOk();
        $this->assertEquals(0, $response->json('totalEvents'));
        $this->assertEquals(0.0, $response->json('successRate'));
    }

    // 4. IP masking

    public function test_ip_addresses_are_masked_in_list_response(): void
    {
        $this->makeAuditLog(['ip_address' => '192.168.1.100']);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs');

        $response->assertOk();
        $this->assertStringContainsString('192.xxx.xxx.xxx', $response->content());
        $this->assertStringNotContainsString('192.168.1.100', $response->content());
    }

    public function test_ip_addresses_are_masked_in_detail_response(): void
    {
        $log = $this->makeAuditLog(['ip_address' => '10.20.30.40']);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs/' . $log->id);

        $response->assertOk();
        $this->assertStringContainsString('10.xxx.xxx.xxx', $response->content());
        $this->assertStringNotContainsString('10.20.30.40', $response->content());
    }

    public function test_details_returns_404_for_nonexistent_log_id(): void
    {
        $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs/nonexistent-id')
            ->assertStatus(404);
    }

    public function test_details_returns_complete_log(): void
    {
        $log = $this->makeAuditLog();

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs/' . $log->id);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['id', 'action', 'targetType', 'status']]);
    }

    // 5. Export

    public function test_export_returns_csv_file(): void
    {
        $this->makeAuditLog(['action' => 'user_login', 'ip_address' => '192.168.1.100']);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs/export?format=csv');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('user_login', $response->content());
        $this->assertStringContainsString('192.xxx.xxx.xxx', $response->content());
    }

    public function test_export_returns_json_file(): void
    {
        $this->makeAuditLog(['action' => 'user_login', 'ip_address' => '192.168.1.100']);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs/export?format=json');

        $response->assertOk();
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertJson($response->content());
    }

    public function test_export_sanitizes_sensitive_data(): void
    {
        $this->makeAuditLog(['action' => 'user_login', 'ip_address' => '192.168.1.100']);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs/export?format=csv');

        $response->assertOk();
        $content = $response->content();
        $this->assertStringContainsString('192.xxx.xxx.xxx', $content);
        $this->assertStringNotContainsString('192.168.1.100', $content);
    }

    // 6. Bulk tag

    public function test_bulk_tag_adds_tags_to_multiple_logs(): void
    {
        $log1 = $this->makeAuditLog();
        $log2 = $this->makeAuditLog();

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->postJson('/api/admin/compliance/audit-logs/bulk-tag', [
                'logIds' => [$log1->id, $log2->id],
                'tag' => 'reviewed',
            ]);

        $response->assertOk()
            ->assertJson(['updated' => 2]);

        $this->assertDatabaseHas('audit_log_tags', [
            'audit_log_id' => $log1->id,
            'tag' => 'reviewed',
        ]);
    }

        public function test_bulk_tag_creates_audit_entry(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin, 'sanctum');
        $log = $this->makeAuditLog();

        $response = $this->postJson('/api/admin/compliance/audit-logs/bulk-tag', [
            'logIds' => [$log->id],
            'tag' => 'reviewed',
        ]);

        $response->assertOk();
        $this->assertTrue(AuditLog::where('action', 'compliance.audit_logs.bulk_tag')->exists());
    }

    public function test_bulk_tag_rejects_invalid_log_ids(): void
    {
                $this->actingAs($this->makeAdmin(), 'sanctum');
        $this->postJson('/api/admin/compliance/audit-logs/bulk-tag', [
            'logIds' => ['nonexistent-id'],
            'tag' => 'reviewed',
        ])->assertStatus(422);
    }

    public function test_bulk_tag_403_for_non_admin(): void
    {
                $this->actingAs($this->makeRegularUser(), 'sanctum');
        $this->postJson('/api/admin/compliance/audit-logs/bulk-tag', [
            'logIds' => [],
            'tag' => 'reviewed',
        ])->assertForbidden();
    }

    // 7. Reports

    public function test_generate_report_returns_job_id(): void
    {
        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->postJson('/api/admin/compliance/reports/generate', [
                'reportCode' => 'access_audit',
                'filters' => [],
            ]);

                $response->assertOk()
            ->assertJsonStructure(['id', 'jobId', 'status']);
    }

    public function test_generate_report_403_for_non_admin(): void
    {
        $response = $this->actingAs($this->makeRegularUser(), 'sanctum')
            ->postJson('/api/admin/compliance/reports/generate', [
                'reportCode' => 'access_audit',
                'filters' => [],
            ]);

        $response->assertForbidden();
    }

    public function test_generate_report_401_without_auth(): void
    {
        $this->postJson('/api/admin/compliance/reports/generate', [
            'reportCode' => 'access_audit',
            'filters' => [],
        ])->assertUnauthorized();
    }

    // 8. Rate limiting

        public function test_list_rate_limited_after_20_requests(): void
    {
        Cache::flush();
        RateLimiter::clear('compliance-list');

        $admin = $this->makeAdmin();

        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($admin, 'sanctum')
                ->getJson('/api/admin/compliance/audit-logs')
                ->assertStatus(200);
        }

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs')
            ->assertStatus(429);
    }

    public function test_export_rate_limited_after_5_requests(): void
    {
                Cache::flush();
        RateLimiter::clear('compliance-export');

        $admin = $this->makeAdmin();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($admin, 'sanctum')
                ->getJson('/api/admin/compliance/audit-logs/export')
                ->assertStatus(200);
        }

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs/export')
            ->assertStatus(429);
    }

    public function test_bulk_tag_rate_limited_after_10_requests(): void
    {
                Cache::flush();
        RateLimiter::clear('compliance-bulk-tag');

        $admin = $this->makeAdmin();

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($admin, 'sanctum')
                ->postJson('/api/admin/compliance/audit-logs/bulk-tag', ['logIds' => [], 'tag' => 'x'])
                ->assertStatus(422);
        }

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/compliance/audit-logs/bulk-tag', ['logIds' => [], 'tag' => 'x'])
            ->assertStatus(429);
    }

    public function test_report_generation_rate_limited_after_5_requests(): void
    {
                Cache::flush();
        RateLimiter::clear('compliance-report-generate');

        $admin = $this->makeAdmin();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($admin, 'sanctum')
                ->postJson('/api/admin/compliance/reports/generate', ['reportCode' => 'access_audit', 'filters' => []])
                ->assertStatus(200);
        }

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/compliance/reports/generate', ['reportCode' => 'access_audit', 'filters' => []])
            ->assertStatus(429);
    }

    // 9. Edge cases

    public function test_list_with_no_logs_returns_empty_data(): void
    {
        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs');

        $response->assertOk()
            ->assertJson(['data' => []]);
    }

    public function test_list_pagination_works(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->makeAuditLog();
        }

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs?per_page=2');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $this->assertEquals(5, $response->json('meta.total'));
    }

    public function test_all_endpoints_reject_without_auth(): void
    {
        $endpoints = [
            '/api/admin/compliance/audit-logs',
            '/api/admin/compliance/audit-logs/export',
            '/api/admin/compliance/audit-logs/summary',
            '/api/admin/compliance/reports',
        ];

        foreach ($endpoints as $endpoint) {
            $this->getJson($endpoint)->assertStatus(401);
        }
    }

    public function test_all_endpoints_reject_non_admin(): void
    {
        $user = $this->makeRegularUser();

        $endpoints = [
            '/api/admin/compliance/audit-logs',
            '/api/admin/compliance/audit-logs/export',
            '/api/admin/compliance/audit-logs/summary',
            '/api/admin/compliance/reports',
        ];

        foreach ($endpoints as $endpoint) {
            $this->actingAs($user, 'sanctum')
                ->getJson($endpoint)
                ->assertForbidden();
        }
    }

    public function test_full_flow_filters_export_and_bulk_tag(): void
    {
        $admin = $this->makeAdmin();
        $log1 = $this->makeAuditLog(['action' => 'user_login', 'status' => 'success']);
        $log2 = $this->makeAuditLog(['action' => 'payment_processed', 'status' => 'failure']);

        $listResponse = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs?action=user_login');
        $listResponse->assertOk();
        $this->assertCount(1, $listResponse->json('data'));

        $exportResponse = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs/export?format=json');
        $exportResponse->assertOk();
        $this->assertJson($exportResponse->content());

        $bulkResponse = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/compliance/audit-logs/bulk-tag', [
                'logIds' => [$log1->id, $log2->id],
                'tag' => 'reviewed',
            ]);
        $bulkResponse->assertOk()->assertJson(['updated' => 2]);
    }
}
