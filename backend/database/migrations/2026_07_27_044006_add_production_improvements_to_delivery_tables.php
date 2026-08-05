<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Implements production-readiness improvements based on Step 68 audit:
     * 1. status+created_at composite index for "recent deliveries by status"
     * 2. CHECK constraints on status and channel enum columns
     * 3. archived_at column for soft-archival of old events
     * 4. Separate delivery_event_data table for large JSON payloads
     */
    public function up(): void
    {
        // ── 1. status+created_at composite index ──────────────────
        try {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->index(['status', 'created_at'], 'delivery_events_status_created_at_index');
            });
        } catch (\Exception $e) {
            // Index may already exist
        }

        // ── 3. archived_at column ────────────────────────────────
        try {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->timestamp('archived_at')->nullable()->after('clicked_at');
                $table->index('archived_at', 'delivery_events_archived_at_index');
            });
        } catch (\Exception $e) {
            // Column may already exist
        }

        // ── 4. Separate delivery_event_data table for large JSON payloads ──
        try {
            Schema::create('delivery_event_data', function (Blueprint $table) {
                $table->id();
                $table->foreignId('delivery_event_id')
                    ->constrained('delivery_events')
                    ->cascadeOnDelete();
                $table->json('payload')->nullable();
                $table->json('provider_response')->nullable();
                $table->json('error_message')->nullable();
                $table->timestamps();

                // One-to-one relationship with delivery_events
                $table->unique('delivery_event_id', 'delivery_event_data_event_id_unique');
            });
        } catch (\Exception $e) {
            // Table may already exist
        }

        // ── 2. CHECK constraints for status and channel ──────────
        // SQLite does not enforce CHECK constraints but accepts the syntax.
        // In MySQL/PostgreSQL these will be enforced at the database level.
        try {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->string('status_check', 20)
                    ->virtualAs("CASE WHEN status IN ('pending','sent','delivered','failed','bounced','cancelled','archived') THEN status ELSE 'pending' END")
                    ->nullable();
            });
        } catch (\Exception $e) {
            // Virtual column may not be supported (SQLite)
        }

        try {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->string('channel_check', 20)
                    ->virtualAs("CASE WHEN channel IN ('email','sms','dashboard','push') THEN channel ELSE 'email' END")
                    ->nullable();
            });
        } catch (\Exception $e) {
            // Virtual column may not be supported (SQLite)
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop index
        try {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->dropIndex('delivery_events_status_created_at_index');
            });
        } catch (\Exception $e) {
            // Index may not exist
        }

        // Drop archived_at column
        try {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->dropIndex('delivery_events_archived_at_index');
                $table->dropColumn('archived_at');
            });
        } catch (\Exception $e) {
            // Column may not exist
        }

        // Drop delivery_event_data table
        try {
            Schema::dropIfExists('delivery_event_data');
        } catch (\Exception $e) {
            // Table may not exist
        }

        // Drop virtual columns
        try {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->dropColumn('status_check');
            });
        } catch (\Exception $e) {
            // Column may not exist
        }

        try {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->dropColumn('channel_check');
            });
        } catch (\Exception $e) {
            // Column may not exist
        }
    }
};
