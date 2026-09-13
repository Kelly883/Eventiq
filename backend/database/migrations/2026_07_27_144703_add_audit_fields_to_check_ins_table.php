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
        $hasCheckInsEventId = Schema::hasColumn('check_ins', 'event_id');
        $hasCheckInsScannedBy = Schema::hasColumn('check_ins', 'scanned_by');
        $hasCheckInsStatus = Schema::hasColumn('check_ins', 'status');
        $hasCheckInsDeviceType = Schema::hasColumn('check_ins', 'device_type');
        $hasCheckInsDeviceId = Schema::hasColumn('check_ins', 'device_id');
        $hasCheckInsIpAddress = Schema::hasColumn('check_ins', 'ip_address');
        $hasCheckInsUserAgent = Schema::hasColumn('check_ins', 'user_agent');
        $hasCheckInsQrVerified = Schema::hasColumn('check_ins', 'qr_verified');
        $hasCheckInsFailureReason = Schema::hasColumn('check_ins', 'failure_reason');
        Schema::table('check_ins', function (Blueprint $table) use ($hasCheckInsEventId, $hasCheckInsScannedBy, $hasCheckInsStatus, $hasCheckInsDeviceType, $hasCheckInsDeviceId, $hasCheckInsIpAddress, $hasCheckInsUserAgent, $hasCheckInsQrVerified, $hasCheckInsFailureReason) {
            // ── Additional Foreign Keys ─────────────────────────────
            if (! $hasCheckInsEventId) {
                $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            }

            if (! $hasCheckInsScannedBy) {
                $table->uuid('scanned_by')->nullable();
                $table->foreign('scanned_by')->references('id')->on('users')->nullOnDelete();
            }

            // ── Scan Context ─────────────────────────────────────────
            if (! $hasCheckInsStatus) {
                $table->string('status')->default('checked_in')->after('user_id');
            }

            if (! $hasCheckInsDeviceType) {
                $table->string('device_type')->nullable()->after('status');
            }

            if (! $hasCheckInsDeviceId) {
                $table->string('device_id')->nullable()->after('device_type');
            }

            if (! $hasCheckInsIpAddress) {
                $table->string('ip_address')->nullable()->after('device_id');
            }

            if (! $hasCheckInsUserAgent) {
                $table->string('user_agent')->nullable()->after('ip_address');
            }

            // ── QR Verification ──────────────────────────────────────
            if (! $hasCheckInsQrVerified) {
                $table->boolean('qr_verified')->default(true)->after('user_agent');
            }

            if (! $hasCheckInsFailureReason) {
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
            Schema::table('check_ins', function (Blueprint $table) use ($existing) {
            $table->dropIndex('check_ins_scanned_at_index');
        
            $table->dropIndex('check_ins_scanned_by_index');
        
            $table->dropIndex('check_ins_ticket_id_scanned_at_index');
        
            $table->dropIndex('check_ins_event_id_scanned_at_index');
        
            $table->dropColumn($existing);
        });
        }
    }
};