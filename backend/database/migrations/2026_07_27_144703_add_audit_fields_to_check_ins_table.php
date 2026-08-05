<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds missing audit-trail columns and indexes to the existing
     * check_ins table created by 2026_07_06_130000_create_check_ins_table.php.
     * This preserves the original table and extends it with:
     *   - event_id / scanned_by FKs
     *   - status, device info, QR verification fields
     *   - composite indexes for analytics and per-ticket history
     */
    public function up(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            // ── Additional Foreign Keys ─────────────────────────────
            if (! Schema::hasColumn('check_ins', 'event_id')) {
                $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('check_ins', 'scanned_by')) {
                $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            }

            // ── Scan Context ─────────────────────────────────────────
            if (! Schema::hasColumn('check_ins', 'status')) {
                $table->string('status')->default('checked_in')->after('user_id');
            }

            if (! Schema::hasColumn('check_ins', 'device_type')) {
                $table->string('device_type')->nullable()->after('status');
            }

            if (! Schema::hasColumn('check_ins', 'device_id')) {
                $table->string('device_id')->nullable()->after('device_type');
            }

            if (! Schema::hasColumn('check_ins', 'ip_address')) {
                $table->string('ip_address')->nullable()->after('device_id');
            }

            if (! Schema::hasColumn('check_ins', 'user_agent')) {
                $table->string('user_agent')->nullable()->after('ip_address');
            }

            // ── QR Verification ──────────────────────────────────────
            if (! Schema::hasColumn('check_ins', 'qr_verified')) {
                $table->boolean('qr_verified')->default(true)->after('user_agent');
            }

            if (! Schema::hasColumn('check_ins', 'failure_reason')) {
                $table->text('failure_reason')->nullable()->after('qr_verified');
            }

            // ── Indexes ───────────────────────────────────────────────
            // Fast: "all check-ins for event X ordered by time"
            try {
                $table->index(['event_id', 'checked_in_at'], 'check_ins_event_id_scanned_at_index');
            } catch (\Exception $e) {
                // Index may already exist
            }

            // Fast: "all scans for ticket X ordered by time"
            try {
                $table->index(['ticket_id', 'checked_in_at'], 'check_ins_ticket_id_scanned_at_index');
            } catch (\Exception $e) {
                // Index may already exist
            }

            // Fast: "all scans performed by staff member Y"
            try {
                $table->index('scanned_by', 'check_ins_scanned_by_index');
            } catch (\Exception $e) {
                // Index may already exist
            }

            // Fast: "all scans across events in a date range"
            try {
                $table->index('checked_in_at', 'check_ins_scanned_at_index');
            } catch (\Exception $e) {
                // Index may already exist
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            // Drop indexes first
            try { $table->dropIndex('check_ins_event_id_scanned_at_index'); } catch (\Exception $e) {}
            try { $table->dropIndex('check_ins_ticket_id_scanned_at_index'); } catch (\Exception $e) {}
            try { $table->dropIndex('check_ins_scanned_by_index'); } catch (\Exception $e) {}
            try { $table->dropIndex('check_ins_scanned_at_index'); } catch (\Exception $e) {}

            // Drop foreign keys
            try { $table->dropForeign(['event_id']); } catch (\Exception $e) {}
            try { $table->dropForeign(['scanned_by']); } catch (\Exception $e) {}

            // Drop columns
            $columns = [
                'event_id', 'scanned_by', 'status', 'device_type', 'device_id',
                'ip_address', 'user_agent', 'qr_verified', 'failure_reason',
            ];

            $existing = [];
            foreach ($columns as $col) {
                if (Schema::hasColumn('check_ins', $col)) {
                    $existing[] = $col;
                }
            }

            if (! empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};