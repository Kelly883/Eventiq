<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds performance-optimization indexes and unique constraints
     * to delivery_events and delivery_preferences tables based on
     * query-pattern analysis for high-volume (millions of rows) usage.
     *
     * Uses try/catch for SQLite compatibility (no Doctrine schema manager).
     */
    public function up(): void
    {
        // ── delivery_events: Composite Indexes ────────────────────
        // Composite: ticket_id + status — for "delivery history for a ticket by status"
        try {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->index(['ticket_id', 'status']);
            });
        } catch (\Exception $e) {
            // Index may already exist
        }

        // Composite: user_id + created_at — for "recent deliveries for a user"
        try {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->index(['user_id', 'created_at']);
            });
        } catch (\Exception $e) {
            // Index may already exist
        }

        // Composite: event_id + created_at — for "recent deliveries for an event"
        try {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->index(['event_id', 'created_at']);
            });
        } catch (\Exception $e) {
            // Index may already exist
        }

        // ── delivery_preferences: Unique Constraints ──────────────
        // Unique: email_address — prevent duplicate delivery emails across users
        try {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->unique('email_address');
            });
        } catch (\Exception $e) {
            // Unique constraint may already exist
        }

        // Unique: phone_number — prevent duplicate delivery phone numbers across users
        try {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->unique('phone_number');
            });
        } catch (\Exception $e) {
            // Unique constraint may already exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_events', function (Blueprint $table) {
            try {
                $table->dropIndex(['ticket_id', 'status']);
            } catch (\Exception $e) {
                // Index may not exist
            }

            try {
                $table->dropIndex(['user_id', 'created_at']);
            } catch (\Exception $e) {
                // Index may not exist
            }

            try {
                $table->dropIndex(['event_id', 'created_at']);
            } catch (\Exception $e) {
                // Index may not exist
            }
        });

        Schema::table('delivery_preferences', function (Blueprint $table) {
            try {
                $table->dropUnique(['email_address']);
            } catch (\Exception $e) {
                // Unique constraint may not exist
            }

            try {
                $table->dropUnique(['phone_number']);
            } catch (\Exception $e) {
                // Unique constraint may not exist
            }
        });
    }
};
