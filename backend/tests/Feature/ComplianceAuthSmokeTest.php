<?php

namespace Tests\Feature;

use App\Features\Compliance\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ComplianceAuthSmokeTest extends TestCase
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

    public function test_audit_logs_index_requires_auth(): void
    {
        $this->getJson('/api/admin/compliance/audit-logs')->assertStatus(401);
    }

    public function test_audit_logs_index_requires_admin(): void
    {
        $this->actingAs($this->makeRegularUser(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs')
            ->assertForbidden();
    }

    public function test_audit_logs_index_returns_data_for_admin(): void
    {
        $this->makeAuditLog();

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs');

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_audit_log_export_requires_admin(): void
    {
        $this->actingAs($this->makeRegularUser(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs/export')
            ->assertForbidden();
    }

    public function test_bulk_tag_requires_admin(): void
    {
        $this->actingAs($this->makeRegularUser(), 'sanctum')
            ->postJson('/api/admin/compliance/audit-logs/bulk-tag', [
                'logIds' => [],
                'tag' => 'reviewed',
            ])
            ->assertForbidden();
    }

    public function test_bulk_tag_succeeds_for_admin(): void
    {
        $log = $this->makeAuditLog();

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->postJson('/api/admin/compliance/audit-logs/bulk-tag', [
                'logIds' => [$log->id],
                'tag' => 'reviewed',
            ]);

        $response->assertOk()
            ->assertJson(['updated' => 1]);
    }

    public function test_summary_requires_auth(): void
    {
        $this->getJson('/api/admin/compliance/audit-logs/summary')->assertStatus(401);
    }

    public function test_summary_returns_metrics_for_admin(): void
    {
        $this->makeAuditLog(['status' => 'success']);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs/summary');

        $response->assertOk()
            ->assertJsonStructure(['totalEvents', 'successRate', 'failedCount']);
    }
}
