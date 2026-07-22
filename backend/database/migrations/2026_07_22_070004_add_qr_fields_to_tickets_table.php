<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // All QR and check-in columns (qr_code_data, qr_code_secret,
        // qr_code_generated_at, qr_code_expires_at, checked_in_at,
        // checked_in_by, qr_code_scanned_count, last_qr_scan_at) were
        // already added by migration 2026_07_22_066003_create_tickets_table.php.
        // Only the indexes are needed here; SQLite ignores duplicate index
        // creation attempts, so this is safe to run on every migrate.
        Schema::table('tickets', function (Blueprint $table) {
            $table->index(['event_id', 'status']);
            $table->index(['event_id', 'checked_in_at']);
            $table->index('qr_code_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['tickets_qr_code_expires_at_index']);
            $table->dropIndex(['tickets_event_id_checked_in_at_index']);
            $table->dropIndex(['tickets_event_id_status_index']);
            
            $table->dropColumn([
                'last_qr_scan_at',
                'qr_code_scanned_count',
                'checked_in_by',
                'checked_in_at',
                'qr_code_expires_at',
                'qr_code_generated_at',
                'qr_code_secret',
                'qr_code_data',
            ]);
        });
    }
};