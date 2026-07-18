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
        Log::shouldReceive('channel')->once()->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->once()->with('refund.requested', \Mockery::on(
            fn (array $context): bool => $context['entity'] === 'refund_request'
                && $context['entity_id'] === 123
                && $context['user_id'] === 45
                && $context['request_id'] === 'req-test-123'
        ));

        $auditLog = app(AuditLogService::class)->log(
            'refund.requested',
            'refund_request',
            123,
            ['status' => 'pending'],
            45,
            'req-test-123'
        );

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'refund.requested',
            'entity' => 'refund_request',
            'entity_id' => 123,
            'user_id' => 45,
            'request_id' => 'req-test-123',
        ]);
    }
}
