<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the missing event_type + created_at composite index for
     * dashboard queries filtering by fraud type over time.
     */
    public function up(): void
    {
        Schema::table('fraud_events', function (Blueprint $table) {
            $table->index(['event_type', 'created_at'], 'idx_fraud_type_created');
        });
    }

    public function down(): void
    {
        Schema::table('fraud_events', function (Blueprint $table) {
            $table->dropIndex('idx_fraud_type_created');
        });
    }
};