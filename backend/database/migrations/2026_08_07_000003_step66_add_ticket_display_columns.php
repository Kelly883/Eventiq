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

        $hasTicketsTicketId = Schema::hasColumn('tickets', 'ticket_id');
        $hasTicketsAttendeeName = Schema::hasColumn('tickets', 'attendee_name');
        $hasTicketsAttendeeEmail = Schema::hasColumn('tickets', 'attendee_email');
        $hasTicketsTier = Schema::hasColumn('tickets', 'tier');
        Schema::table('tickets', function (Blueprint $table) use ($hasTicketsTicketId, $hasTicketsAttendeeName, $hasTicketsAttendeeEmail, $hasTicketsTier) {
            if (! $hasTicketsTicketId) {
                $table->string('ticket_id')->unique()->nullable()->after('id');
            }
            if (! $hasTicketsAttendeeName) {
                $table->string('attendee_name')->nullable()->after('ticket_id');
            }
            if (! $hasTicketsAttendeeEmail) {
                $table->string('attendee_email')->nullable()->after('attendee_name');
            }
            if (! $hasTicketsTier) {
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

        $columns = ['ticket_id', 'attendee_name', 'attendee_email', 'tier'];
        $existing = [];
        foreach ($columns as $column) {
            if (Schema::hasColumn('tickets', $column)) {
                $existing[] = $column;
            }
        }
        if (! empty($existing)) {
            Schema::table('tickets', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }
};