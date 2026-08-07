<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Step 66 production readiness - ticket display columns.
 *
 * The WebhookController (which issues tickets after a successful payment)
 * writes ticket_id, attendee_name, attendee_email, and tier on the tickets
 * table. These columns were defined in migration 2026_07_27_150001_
 * update_tickets_table_for_step71.php, but that migration was never run
 * against this SQLite database. Add them safely on all engines.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'ticket_id')) {
                $table->string('ticket_id')->unique()->nullable()->after('id');
            }
            if (! Schema::hasColumn('tickets', 'attendee_name')) {
                $table->string('attendee_name')->nullable()->after('ticket_id');
            }
            if (! Schema::hasColumn('tickets', 'attendee_email')) {
                $table->string('attendee_email')->nullable()->after('attendee_name');
            }
            if (! Schema::hasColumn('tickets', 'tier')) {
                $table->string('tier')->nullable()->after('attendee_email');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement("ALTER TABLE tickets ADD INDEX idx_tickets_ticket_id_unique (ticket_id)");
            } catch (\Throwable) {
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            foreach (['ticket_id', 'attendee_name', 'attendee_email', 'tier'] as $column) {
                if (Schema::hasColumn('tickets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};