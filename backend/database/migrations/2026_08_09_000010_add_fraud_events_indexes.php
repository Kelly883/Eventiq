<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fraud_events')) {
            return;
        }

        $indexes = [
            'idx_fraud_user_created' => 'CREATE INDEX IF NOT EXISTS "idx_fraud_user_created" ON "fraud_events" ("user_id", "created_at")',
            'idx_fraud_status_created' => 'CREATE INDEX IF NOT EXISTS "idx_fraud_status_created" ON "fraud_events" ("status", "created_at")',
            'idx_fraud_risk_created' => 'CREATE INDEX IF NOT EXISTS "idx_fraud_risk_created" ON "fraud_events" ("risk_level", "created_at")',
            'idx_fraud_order_type_unique' => 'CREATE UNIQUE INDEX IF NOT EXISTS "idx_fraud_order_type_unique" ON "fraud_events" ("order_id", "fraud_type")',
            'idx_fraud_event_detected' => 'CREATE INDEX IF NOT EXISTS "idx_fraud_event_detected" ON "fraud_events" ("event_id", "detected_at")',
            'idx_fraud_reviewer_status' => 'CREATE INDEX IF NOT EXISTS "idx_fraud_reviewer_status" ON "fraud_events" ("reviewed_by", "status")',
            'idx_fraud_created_at' => 'CREATE INDEX IF NOT EXISTS "idx_fraud_created_at" ON "fraud_events" ("created_at")',
            'idx_fraud_ip_address' => 'CREATE INDEX IF NOT EXISTS "idx_fraud_ip_address" ON "fraud_events" ("ip_address")',
            'idx_fraud_card_fingerprint' => 'CREATE INDEX IF NOT EXISTS "idx_fraud_card_fingerprint" ON "fraud_events" ("card_fingerprint")',
        ];

        foreach ($indexes as $name => $sql) {
            try {
                DB::statement($sql);
            } catch (\Exception $e) {
            }
        }
    }

    public function down(): void
    {
        $indexes = [
            'idx_fraud_user_created',
            'idx_fraud_status_created',
            'idx_fraud_risk_created',
            'idx_fraud_order_type_unique',
            'idx_fraud_event_detected',
            'idx_fraud_reviewer_status',
            'idx_fraud_created_at',
            'idx_fraud_ip_address',
            'idx_fraud_card_fingerprint',
        ];

        foreach ($indexes as $idx) {
            try {
                DB::statement('DROP INDEX IF EXISTS "' . $idx . '"');
            } catch (\Exception $e) {
            }
        }
    }
};
