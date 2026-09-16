<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // qr_code_expires_at is defined by 2026_07_22_066003_create_tickets_table.php,
        // but that migration is skipped when the tickets table already exists.
        // Ensure the column exists before indexing it.
        if (Schema::hasTable('tickets') && !Schema::hasColumn('tickets', 'qr_code_expires_at')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->text('qr_code_data')->nullable();
                $table->string('qr_code_secret')->nullable();
                $table->timestamp('qr_code_generated_at')->nullable();
                $table->timestamp('qr_code_expires_at')->nullable();
                $table->integer('qr_code_scanned_count')->default(0);
                $table->timestamp('last_qr_scan_at')->nullable();

                // Add indexes when creating the column on a fresh database.
                $table->index(['event_id', 'status'], 'tickets_event_id_status_index');
                $table->index(['event_id', 'checked_in_at'], 'tickets_event_id_checked_in_at_index');
                $table->index('qr_code_expires_at', 'tickets_qr_code_expires_at_index');
            });
        } else {
            // Column already exists (from 066003 or earlier migration).
            // Ensure each index exists independently — create only missing ones.
            Schema::table('tickets', function (Blueprint $table) {
                if (!Schema::hasIndex('tickets', 'tickets_qr_code_expires_at_index')) {
                    $table->index('qr_code_expires_at', 'tickets_qr_code_expires_at_index');
                }
                if (!Schema::hasIndex('tickets', 'tickets_event_id_status_index')) {
                    $table->index(['event_id', 'status'], 'tickets_event_id_status_index');
                }
                if (!Schema::hasIndex('tickets', 'tickets_event_id_checked_in_at_index')) {
                    $table->index(['event_id', 'checked_in_at'], 'tickets_event_id_checked_in_at_index');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['tickets_qr_code_expires_at_index']);
            $table->dropIndex(['tickets_event_id_checked_in_at_index']);
            $table->dropIndex(['tickets_event_id_status_index']);
        });
    }
};