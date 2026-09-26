<?php

namespace Tests\Feature;

use App\Features\Compliance\Enums\AuditLogAction;
use App\Features\Compliance\Enums\AuditLogStatus;
use App\Features\Compliance\Enums\ComplianceClassification;
use App\Features\Compliance\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceAuditLogEndpointTest extends TestCase
{
    use RefreshDatabase;

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

    private function createAuditLog(array $overrides = []): AuditLog
    {
        return AuditLog::create(array_merge([
            'user_id' => $this->makeAdmin()->id,
            'action' => AuditLogAction::USER_LOGIN->value,
            'target_type' => 'user',
            'target_id' => 'test-target-id',
            'description' => 'Test audit log entry',
            'status' => AuditLogStatus::SUCCESS->value,
            'compliance_classification' => ComplianceClassification::INTERNAL->value,
            'ip_address' => '203.0.113.42',
            'user_agent' => 'Mozilla/5.0',
            'source' => 'web',
            'changed_fields' => ['field' => 'value'],
            'metadata' => ['requestId' => 'req-123'],
            'retention_date' => now()->addYears(7),
        ], $overrides));
    }

    public function test_index_filters_by_action(): void
    {
        $this->createAuditLog(['action' => AuditLogAction::USER_LOGIN->value]);
        $this->createAuditLog(['action' => AuditLogAction::EVENT_CREATED->value]);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs?action=user_login');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('user_login', $response->json('data.0.action'));
    }

    public function test_index_filters_by_target_type(): void
    {
        $this->createAuditLog(['target_type' => 'user']);
        $this->createAuditLog(['target_type' => 'event']);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs?targetType=event');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('event', $response->json('data.0.targetType'));
    }

    public function test_index_filters_by_status(): void
    {
        $this->createAuditLog(['status' => AuditLogStatus::SUCCESS->value]);
        $this->createAuditLog(['status' => AuditLogStatus::FAILURE->value]);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs?status=failure');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('failure', $response->json('data.0.status'));
    }

    public function test_index_filters_by_classification(): void
    {
        $this->createAuditLog(['compliance_classification' => ComplianceClassification::INTERNAL->value]);
        $this->createAuditLog(['compliance_classification' => ComplianceClassification::RESTRICTED->value]);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs?classification=restricted');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('restricted', $response->json('data.0.complianceClassification'));
    }

    public function test_index_filters_by_user_id(): void
    {
        $admin = $this->makeAdmin();
        $otherAdmin = $this->makeAdmin();

        $this->createAuditLog(['user_id' => $admin->id]);
        $this->createAuditLog(['user_id' => $otherAdmin->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs?userId=' . $admin->id);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals((string) $admin->id, $response->json('data.0.userId'));
    }

    public function test_index_sorts_by_action(): void
    {
        $this->createAuditLog(['action' => AuditLogAction::TICKET_VOIDED->value]);
        $this->createAuditLog(['action' => AuditLogAction::USER_LOGIN->value]);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs?sortBy=action&sortOrder=asc');

        $response->assertOk();
        $this->assertEquals('ticket_voided', $response->json('data.0.action'));
        $this->assertEquals('user_login', $response->json('data.1.action'));
    }

    public function test_index_sorts_by_status(): void
    {
        $this->createAuditLog(['status' => AuditLogStatus::SUCCESS->value]);
        $this->createAuditLog(['status' => AuditLogStatus::FAILURE->value]);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs?sortBy=status&sortOrder=asc');

        $response->assertOk();
        $this->assertEquals('failure', $response->json('data.0.status'));
        $this->assertEquals('success', $response->json('data.1.status'));
    }

    public function test_index_returns_summary_metrics(): void
    {
        $this->createAuditLog(['status' => 'success']);
        $this->createAuditLog(['status' => 'failure']);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs');

        $response->assertOk();
        $this->assertArrayHasKey('summary', $response->json());
        $this->assertArrayHasKey('totalEvents', $response->json('summary'));
        $this->assertArrayHasKey('successRate', $response->json('summary'));
        $this->assertArrayHasKey('failedCount', $response->json('summary'));
    }

    public function test_index_masks_ip_addresses(): void
    {
        $this->createAuditLog(['ip_address' => '203.0.113.42']);
        $this->createAuditLog(['ip_address' => '2001:db8::1']);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs');

        $response->assertOk();
        $ipAddresses = collect($response->json('data'))->pluck('ipAddress');
        $this->assertTrue($ipAddresses->contains('203.xxx.xxx.xxx'));
        $this->assertTrue($ipAddresses->contains('2001:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx'));
    }

    public function test_export_returns_csv_content(): void
    {
        $this->createAuditLog(['action' => AuditLogAction::USER_LOGIN->value, 'ip_address' => '203.0.113.42']);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs/export?format=csv');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8')
            ->assertHeader('Content-Disposition');
        $this->assertStringContainsString('user_login', $response->content());
        $this->assertStringContainsString('203.xxx.xxx.xxx', $response->content());
    }

    public function test_export_returns_json_content(): void
    {
        $this->createAuditLog(['action' => 'user_login']);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs/export?format=json');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('Content-Disposition');
        $this->assertJson($response->content());
    }

    public function test_bulk_tag_updates_logs(): void
    {
        $log1 = $this->createAuditLog();
        $log2 = $this->createAuditLog();

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
        $this->assertDatabaseHas('audit_log_tags', [
            'audit_log_id' => $log2->id,
            'tag' => 'reviewed',
        ]);
    }

    public function test_bulk_tag_requires_valid_uuids(): void
    {
        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->postJson('/api/admin/compliance/audit-logs/bulk-tag', [
                'logIds' => ['not-a-uuid'],
                'tag' => 'reviewed',
            ]);

        $response->assertUnprocessable();
    }

    public function test_non_admin_cannot_bulk_tag(): void
    {
        $response = $this->actingAs($this->makeRegularUser(), 'sanctum')
            ->postJson('/api/admin/compliance/audit-logs/bulk-tag', [
                'logIds' => ['not-a-uuid'],
                'tag' => 'reviewed',
            ]);

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_bulk_tag(): void
    {
        $response = $this->postJson('/api/admin/compliance/audit-logs/bulk-tag', [
            'logIds' => ['not-a-uuid'],
            'tag' => 'reviewed',
        ]);

        $response->assertUnauthorized();
    }
}
