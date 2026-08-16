<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds Step 70 QR code and check-in fields to the tickets table.
     * The initial tickets table (2026_07_06_100001) was created before
     * Step 70 was designed, so these fields need to be added now.
     *
     * Fields added:
     * - qr_code_data: base64-encoded encrypted QR payload
     * - qr_code_secret: bcrypt-hashed secret for QR verification
     * - qr_code_generated_at: timestamp of QR generation
     * - qr_code_expires_at: expiry (typically event end + 7 days)
     * - checked_in_at: already exists from earlier migration
     * - checked_in_by: FK to users (already exists, needs proper FK)
     * - qr_code_scanned_count: scan counter
     * - last_qr_scan_at: timestamp of last scan
     *
     * Indexes:
     * - (event_id, status): already exists
     * - (event_id, checked_in_at): already exists
     * - (qr_code_expires_at): for finding expired QRs
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // ── QR Code Fields ────────────────────────────────────
            if (! Schema::hasColumn('tickets', 'qr_code_data')) {
                $table->text('qr_code_data')->nullable()->after('tier');
            }

            if (! Schema::hasColumn('tickets', 'qr_code_secret')) {
                $table->string('qr_code_secret')->nullable()->after('qr_code_data');
            }

            if (! Schema::hasColumn('tickets', 'qr_code_generated_at')) {
                $table->timestamp('qr_code_generated_at')->nullable()->after('qr_code_secret');
            }

            if (! Schema::hasColumn('tickets', 'qr_code_expires_at')) {
                $table->timestamp('qr_code_expires_at')->nullable()->after('qr_code_generated_at');
            }

            // ── QR Scan Tracking ──────────────────────────────────
            if (! Schema::hasColumn('tickets', 'qr_code_scanned_count')) {
                $table->integer('qr_code_scanned_count')->default(0)->after('checked_in_by');
            }

            if (! Schema::hasColumn('tickets', 'last_qr_scan_at')) {
                $table->timestamp('last_qr_scan_at')->nullable()->after('qr_code_scanned_count');
            }

            // ── Fix checked_in_by FK ───────────────────────────────
            // The old migration (2026_07_06_124000) added checked_in_by as
            // unsignedBigInteger without a FK. Ensure the existing column
            // has a proper UUID FK to users. Do NOT drop the column or
            // create checked_in_by_uuid — the model uses checked_in_by.
            try {
                $table->foreign('checked_in_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            } catch (\Exception $e) {
                // FK may already exist
            }
        });

        // ── Indexes ───────────────────────────────────────────────
        // qr_code_expires_at index for finding expired QR codes
        try {
            Schema::table('tickets', function (Blueprint $table) {
                $table->index('qr_code_expires_at', 'idx_tickets_qr_expires');
            });
        } catch (\Exception $e) {
            // Index may already exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Drop index
            try {
                $table->dropIndex('idx_tickets_qr_expires');
            } catch (\Exception $e) {
                // Index may not exist
            }

            // Drop columns that were added
            $columns = [];
            if (Schema::hasColumn('tickets', 'qr_code_data')) {
                $columns[] = 'qr_code_data';
            }
            if (Schema::hasColumn('tickets', 'qr_code_secret')) {
                $columns[] = 'qr_code_secret';
            }
            if (Schema::hasColumn('tickets', 'qr_code_generated_at')) {
                $columns[] = 'qr_code_generated_at';
            }
            if (Schema::hasColumn('tickets', 'qr_code_expires_at')) {
                $columns[] = 'qr_code_expires_at';
            }
            if (Schema::hasColumn('tickets', 'qr_code_scanned_count')) {
                $columns[] = 'qr_code_scanned_count';
            }
            if (Schema::hasColumn('tickets', 'last_qr_scan_at')) {
                $columns[] = 'last_qr_scan_at';
            }

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};