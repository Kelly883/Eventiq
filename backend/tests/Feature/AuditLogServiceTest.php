<?php

namespace Tests\Feature;

use App\Features\Compliance\Services\AuditLogService;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_writes_an_audit_event_to_file_channel_and_database(): void
    {
        $user = \App\Models\User::factory()->create();

        Log::shouldReceive('channel')->once()->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->once()->with('refund.requested', \Mockery::on(
            fn (array $context): bool => $context['target_type'] === 'refund_request'
                && $context['target_id'] === 123
                && $context['user_id'] === $user->id
                && $context['request_id'] === 'req-test-123'
        ));

        $auditLog = app(AuditLogService::class)->log(
            'refund.requested',
            'refund_request',
            123,
            ['status' => 'pending'],
            $user->id,
            'req-test-123'
        );

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'refund.requested',
            'target_type' => 'refund_request',
            'target_id' => 123,
            'user_id' => $user->id,
        ]);
        $this->assertNotNull($auditLog->metadata);
        $this->assertEquals('req-test-123', $auditLog->metadata['requestId']);
    }
}
