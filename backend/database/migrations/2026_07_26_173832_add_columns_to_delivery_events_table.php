<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the full schema to the delivery_events table (previously only
     * had id + timestamps). This migration is designed for SQLite (the
     * current dev database) which has limited ALTER TABLE support —
     * instead of dropping/recreating, we add columns one by one.
     * For production MySQL/PostgreSQL this works natively.
     */
    public function up(): void
    {
        // ── Foreign Keys ──────────────────────────────────────────
        if (! Schema::hasColumn('delivery_events', 'ticket_id')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->foreignId('ticket_id')->nullable()->after('id');
                // FK added after column to avoid ordering issues
            });
        }

        if (! Schema::hasColumn('delivery_events', 'user_id')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->foreignUuid('user_id')->nullable()->after('ticket_id');
            });
        }

        if (! Schema::hasColumn('delivery_events', 'event_id')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->foreignId('event_id')->nullable()->after('user_id');
            });
        }

        if (! Schema::hasColumn('delivery_events', 'order_id')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->foreignId('order_id')->nullable()->after('event_id');
            });
        }

        // ── Channel & Status ──────────────────────────────────────
        if (! Schema::hasColumn('delivery_events', 'channel')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->string('channel')->nullable()->after('order_id');
            });
        }

        if (! Schema::hasColumn('delivery_events', 'status')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->string('status')->default('pending')->after('channel');
            });
        }

        if (! Schema::hasColumn('delivery_events', 'ticket_reference')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->string('ticket_reference')->nullable()->after('status');
            });
        }

        // ── Content ───────────────────────────────────────────────
        if (! Schema::hasColumn('delivery_events', 'recipient')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->string('recipient')->nullable()->after('ticket_reference');
            });
        }

        if (! Schema::hasColumn('delivery_events', 'subject')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->string('subject')->nullable()->after('recipient');
            });
        }

        if (! Schema::hasColumn('delivery_events', 'body')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->text('body')->nullable()->after('subject');
            });
        }

        if (! Schema::hasColumn('delivery_events', 'sender')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->string('sender')->nullable()->after('body');
            });
        }

        if (! Schema::hasColumn('delivery_events', 'payload')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->json('payload')->nullable()->after('sender');
            });
        }

        // ── Provider Details ──────────────────────────────────────
        if (! Schema::hasColumn('delivery_events', 'provider')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->string('provider')->nullable()->after('payload');
            });
        }

        if (! Schema::hasColumn('delivery_events', 'provider_message_id')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->string('provider_message_id')->nullable()->after('provider');
            });
        }

        if (! Schema::hasColumn('delivery_events', 'provider_response')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->text('provider_response')->nullable()->after('provider_message_id');
            });
        }

        if (! Schema::hasColumn('delivery_events', 'error_message')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->text('error_message')->nullable()->after('provider_response');
            });
        }

        // ── Retry / Attempt Tracking ──────────────────────────────
        if (! Schema::hasColumn('delivery_events', 'attempt_count')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->unsignedTinyInteger('attempt_count')->default(0)->after('error_message');
            });
        }

        if (! Schema::hasColumn('delivery_events', 'max_attempts')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->unsignedTinyInteger('max_attempts')->default(3)->after('attempt_count');
            });
        }

        if (! Schema::hasColumn('delivery_events', 'last_attempt_at')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->timestamp('last_attempt_at')->nullable()->after('max_attempts');
            });
        }

        // ── Delivery Timeline ─────────────────────────────────────
        if (! Schema::hasColumn('delivery_events', 'delivered_at')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->timestamp('delivered_at')->nullable()->after('last_attempt_at');
            });
        }

        if (! Schema::hasColumn('delivery_events', 'opened_at')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->timestamp('opened_at')->nullable()->after('delivered_at');
            });
        }

        if (! Schema::hasColumn('delivery_events', 'clicked_at')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->timestamp('clicked_at')->nullable()->after('opened_at');
            });
        }

        // ── Indexes (idempotent, cross-driver safe) ───────────────
        // Avoid doctrine-dependent checks; use explicit names and
        // IF NOT EXISTS so reruns and overlapping migrations stay safe.
        DB::statement('CREATE INDEX IF NOT EXISTS delivery_events_ticket_id_index ON delivery_events (ticket_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS delivery_events_user_id_index ON delivery_events (user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS delivery_events_event_id_index ON delivery_events (event_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS delivery_events_status_index ON delivery_events (status)');
        DB::statement('CREATE INDEX IF NOT EXISTS delivery_events_channel_index ON delivery_events (channel)');
        DB::statement('CREATE INDEX IF NOT EXISTS delivery_events_status_channel_index ON delivery_events (status, channel)');
        DB::statement('CREATE INDEX IF NOT EXISTS delivery_events_created_at_index ON delivery_events (created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS delivery_events_provider_message_id_index ON delivery_events (provider_message_id)');

        // ── Foreign Key Constraints ──────────────────────────────
        // Add FK constraints after all columns exist
        try {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->foreign('ticket_id')->references('id')->on('tickets')->nullOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('event_id')->references('id')->on('events')->nullOnDelete();
                $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            });
        } catch (\Exception $e) {
            // FKs may fail on SQLite if not enabled; that's acceptable for dev
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_events', function (Blueprint $table) {
            // Drop foreign keys first
            try {
                $table->dropForeign(['ticket_id']);
                $table->dropForeign(['user_id']);
                $table->dropForeign(['event_id']);
                $table->dropForeign(['order_id']);
            } catch (\Exception $e) {
                // FK may not exist
            }

            // Drop indexes
            try {
                $table->dropIndex(['ticket_id']);
                $table->dropIndex(['user_id']);
                $table->dropIndex(['event_id']);
                $table->dropIndex(['status']);
                $table->dropIndex(['channel']);
                $table->dropIndex(['status', 'channel']);
                $table->dropIndex(['created_at']);
                $table->dropIndex(['provider_message_id']);
            } catch (\Exception $e) {
                // Index may not exist
            }

            // Drop all added columns
            $table->dropColumn([
                'ticket_id',
                'user_id',
                'event_id',
                'order_id',
                'channel',
                'status',
                'ticket_reference',
                'recipient',
                'subject',
                'body',
                'sender',
                'payload',
                'provider',
                'provider_message_id',
                'provider_response',
                'error_message',
                'attempt_count',
                'max_attempts',
                'last_attempt_at',
                'delivered_at',
                'opened_at',
                'clicked_at',
            ]);
        });
    }
};
