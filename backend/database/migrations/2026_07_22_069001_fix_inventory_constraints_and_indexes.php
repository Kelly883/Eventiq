<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
     private function foreignKeyExists(string $table, string $column): bool
     {
         if (DB::getDriverName() === 'sqlite') {
             $rows = DB::select("PRAGMA foreign_key_list('{$table}')");

             foreach ($rows as $row) {
                 if (($row->from ?? null) === $column) {
                     return true;
                 }
             }

             return false;
         }

         if (DB::getDriverName() === 'pgsql') {
             $row = DB::selectOne(
                 'SELECT c.conname FROM pg_constraint c '
                 . 'JOIN pg_class t ON c.conrelid = t.oid '
                 . 'JOIN pg_namespace n ON t.relnamespace = n.oid '
                 . 'WHERE n.nspname = current_schema() '
                 . 'AND t.relname = ? '
                 . "AND c.contype = 'f' "
                 . 'AND EXISTS ('
                 . '  SELECT 1 FROM pg_attribute a '
                 . '  WHERE a.attrelid = t.oid '
                 . '  AND a.attname = ? '
                 . '  AND a.attnum = ANY(c.conkey)'
                 . ')',
                 [$table, $column]
             );

             return $row !== null;
         }

         $row = DB::selectOne(
             'SELECT column_name FROM information_schema.key_column_usage WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? AND referenced_table_name IS NOT NULL',
             [$table, $column]
         );

         return $row !== null;
     }

     /**
      * Non-destructive post-conversion fixes for ticket_inventory
      * and inventory_adjustments.
      *
      * Assumes 2026_07_21_030001_create_ticket_inventory_table.php has
      * already converted ticket_inventory.id to UUID, and
      * 2026_07_21_030002_create_inventory_adjustments_table.php has
      * rebuilt inventory_adjustments with UUID primary keys.
      *
      * Changes applied without dropping data:
      *   - ticket_inventory: ticket_tier_id FK onDelete RESTRICT
      *   - inventory_adjustments: ticket_inventory_id FK onDelete CASCADE
      */
     public function up(): void
     {
         // -----------------------------------------------------------------
         // Fix 1: Update ticket_inventory FK onDelete behavior
         // -----------------------------------------------------------------
         if (!Schema::hasTable('ticket_inventory')) {
             return;
         }

        if ($this->foreignKeyExists('ticket_inventory', 'ticket_tier_id')) {
              Schema::table('ticket_inventory', function (Blueprint $table) {
                  $table->dropForeign(['ticket_tier_id']);
              });
          }

         Schema::table('ticket_inventory', function (Blueprint $table) {
             $table->foreign('ticket_tier_id')
                 ->references('id')
                 ->on('ticket_tiers')
                 ->onDelete('restrict');
         });

         // -----------------------------------------------------------------
         // Fix 2: Ensure inventory_adjustments.ticket_inventory_id FK exists
         // -----------------------------------------------------------------
         if (!Schema::hasTable('inventory_adjustments')) {
             return;
         }

         if (Schema::hasColumn('inventory_adjustments', 'ticket_inventory_id')) {
             if ($this->foreignKeyExists('inventory_adjustments', 'ticket_inventory_id')) {
                 Schema::table('inventory_adjustments', function (Blueprint $table) {
                     $table->dropForeign(['ticket_inventory_id']);
                 });
             }

             Schema::table('inventory_adjustments', function (Blueprint $table) {
                 $table->foreign('ticket_inventory_id')
                     ->references('id')
                     ->on('ticket_inventory')
                     ->onDelete('cascade');
             });
         }
     }

    public function down(): void
    {
        // -----------------------------------------------------------------
        // NOTE: Reversing the RESTRICT change is straightforward.
        // Reversing the inventory_adjustments rebuild would require
        // re-capturing the current schema state, which is not implemented
        // here because the rebuild is additive and preserves all rows.
        // -----------------------------------------------------------------
    }
};
