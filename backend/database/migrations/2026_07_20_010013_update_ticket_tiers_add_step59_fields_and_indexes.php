<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Disable the implicit transaction wrapper for this migration.
    // On managed PostgreSQL (Render, RDS, etc.), a prior statement failure
    // in the same transaction aborts the entire transaction, causing
    // subsequent CREATE TABLE/ALTER TABLE to fail with "current transaction is aborted".
    // By opting out, each statement runs in its own autocommit transaction.
    public $withinTransaction = false;
    public function up(): void
    {
        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->text('benefits_description')->nullable()->after('quantity');
            $table->string('tier_image_url')->nullable()->after('benefits_description');
            $table->unsignedInteger('max_per_customer')->nullable()->after('early_bird_end_date');

            $table->index(['event_id', 'sales_start_date']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->dropIndex(['ticket_tiers_event_id_sales_start_date_index']);
            $table->dropIndex(['ticket_tiers_created_at_index']);

            $table->dropColumn(['benefits_description', 'tier_image_url', 'max_per_customer']);
        });
    }
};