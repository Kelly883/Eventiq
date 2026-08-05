<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a standalone index on order_id for the delivery_events table
     * to support efficient per-order delivery lookups. The column already
     * exists with a foreign key constraint, but was missing an index for
     * query performance at scale.
     */
    public function up(): void
    {
        try {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->index('order_id', 'delivery_events_order_id_index');
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
        try {
            Schema::table('delivery_events', function (Blueprint $table) {
                $table->dropIndex('delivery_events_order_id_index');
            });
        } catch (\Exception $e) {
            // Index may not exist
        }
    }
};