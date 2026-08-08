<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the missing checked_in boolean and first_scanned_at timestamp
     * to the tickets table. The checked_in column is actively used by
     * CheckInController and MyTicketsController.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'checked_in')) {
                $table->boolean('checked_in')->default(false)->after('status');
            }

            if (! Schema::hasColumn('tickets', 'first_scanned_at')) {
                $table->timestamp('first_scanned_at')->nullable()->after('last_qr_scan_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'checked_in')) {
                $table->dropColumn('checked_in');
            }

            if (Schema::hasColumn('tickets', 'first_scanned_at')) {
                $table->dropColumn('first_scanned_at');
            }
        });
    }
};
