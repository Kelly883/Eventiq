<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ==========================================================
        // NOTE: This migration is now a no-op because:
        //
        // 1. The tables (ticket_inventory, inventory_adjustments) were
        //    already created with the correct schema by migrations
        //    2026_07_21_030001 and 2026_07_21_030002.
        //
        // 2. The corrected is_low_stock virtual column and proper FK
        //    constraints are applied by migration 2026_07_22_069001
        //    (fix_inventory_constraints_and_indexes), which runs after
        //    this one.
        //
        // 3. Attempting to rebuild tables here causes index name
        //    collisions in SQLite (global index namespace).
        //
        // All fixes are handled by 2026_07_22_069001.
        // ==========================================================
    }

    public function down(): void
    {
        // No-op
    }
};
