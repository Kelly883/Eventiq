<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Removes the old `checked_in_by` integer column that was added by
     * migration 2026_07_06_124000. The proper UUID-based column
     * `checked_in_by_uuid` was added by Step 70 and no code references
     * the old column.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'checked_in_by')) {
                // Drop the old integer column
                $table->dropColumn('checked_in_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('checked_in_by')->nullable()->after('checked_in_at');
        });
    }
};