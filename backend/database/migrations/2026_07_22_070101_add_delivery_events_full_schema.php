<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // All columns (ticket_id, order_id, user_id, event_id, delivery_method,
        // recipient_email, recipient_phone, status, delivery_timestamp,
        // viewed_timestamp, attempt_count, max_attempts, last_attempt_at,
        // next_retry_at, error_message, error_code, provider_response,
        // fraud_event_id, delivery_blocked, block_reason, qr_code_data,
        // ticket_pdf_url) were already added by migration
        // 2026_07_22_070001_create_delivery_events_table.php.
        // This migration is kept as a no-op for historical compatibility.
        // SQLite view events_by_date references start_date which doesn't
        // exist in the new schema - drop it to prevent migration failures.
        DB::statement('DROP VIEW IF EXISTS events_by_date');
    }

    public function down(): void
    {
        // No-op: columns are managed by the create migration.
    }
};