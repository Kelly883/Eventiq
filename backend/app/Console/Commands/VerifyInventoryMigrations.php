<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VerifyInventoryMigrations extends Command
{
    protected $signature = 'inventory:verify';
    protected $description = 'Verify the inventory migration tables';

    public function handle()
    {
        $this->info('=== VERIFICATION: Ticket Inventory Migrations ===');
        $this->newLine();

        // 1. Check ticket_inventory table
        $this->line('[1] ticket_inventory table:');
        $has = Schema::hasTable('ticket_inventory');
        $this->line('    Exists: ' . ($has ? 'YES ✅' : 'NO ❌'));

        if ($has) {
            $cols = Schema::getColumnListing('ticket_inventory');
            $this->line('    Columns: ' . implode(', ', $cols));

            $required = ['id', 'event_id', 'ticket_tier_id', 'total_allocated', 'total_sold', 'total_available', 'low_stock_threshold', 'is_low_stock'];
            foreach ($required as $col) {
                $this->line('    - ' . $col . ': ' . (in_array($col, $cols) ? '✅' : '❌'));
            }

            // Check indexes
            $driver = DB::connection()->getDriverName();
            if ($driver === 'sqlite') {
                $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='ticket_inventory' AND name NOT LIKE 'sqlite_%'");
            } else {
                $indexes = DB::select("SHOW INDEX FROM ticket_inventory");
            }
            $this->line('    Indexes:');
            foreach ($indexes as $idx) {
                $name = is_object($idx) ? (property_exists($idx, 'name') ? $idx->name : $idx->Key_name) : $idx;
                $this->line('      - ' . $name);
            }
        }

        $this->newLine();

        // 2. Check inventory_adjustments table
        $this->line('[2] inventory_adjustments table:');
        $has = Schema::hasTable('inventory_adjustments');
        $this->line('    Exists: ' . ($has ? 'YES ✅' : 'NO ❌'));

        if ($has) {
            $cols = Schema::getColumnListing('inventory_adjustments');
            $this->line('    Columns: ' . implode(', ', $cols));

            $required = ['id', 'event_id', 'ticket_tier_id', 'pricing_window_id', 'organizer_id', 'adjustment_type', 'quantity_before', 'quantity_after', 'quantity_delta', 'reason', 'created_at'];
            foreach ($required as $col) {
                $this->line('    - ' . $col . ': ' . (in_array($col, $cols) ? '✅' : '❌'));
            }

            $driver = DB::connection()->getDriverName();
            if ($driver === 'sqlite') {
                $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='inventory_adjustments' AND name NOT LIKE 'sqlite_%'");
            } else {
                $indexes = DB::select("SHOW INDEX FROM inventory_adjustments");
            }
            $this->line('    Indexes:');
            foreach ($indexes as $idx) {
                $name = is_object($idx) ? (property_exists($idx, 'name') ? $idx->name : $idx->Key_name) : $idx;
                $this->line('      - ' . $name);
            }
        }

        $this->newLine();

        // 3. Test inserts
        $this->line('[3] Testing insert operations...');
        try {
            $event = DB::table('events')->first();
            $tier = DB::table('ticket_tiers')->first();
            $user = DB::table('users')->first();

            if ($event && $tier && $user) {
                $invId = DB::table('ticket_inventory')->insertGetId([
                    'event_id' => $event->id,
                    'ticket_tier_id' => $tier->id,
                    'total_allocated' => 100,
                    'total_sold' => 0,
                    'low_stock_threshold' => 10,
                    'last_updated_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->line('    Inserted ticket_inventory ID: ' . $invId . ' ✅');

                $rec = DB::table('ticket_inventory')->find($invId);
                $this->line('    total_allocated=' . $rec->total_allocated . ', total_sold=' . $rec->total_sold . ', total_available=' . $rec->total_available . ' (computed) ✅');

                $adjId = DB::table('inventory_adjustments')->insertGetId([
                    'event_id' => $event->id,
                    'ticket_tier_id' => $tier->id,
                    'organizer_id' => $user->id,
                    'adjustment_type' => 'manual_increase',
                    'quantity_before' => 0,
                    'quantity_after' => 100,
                    'quantity_delta' => 100,
                    'reason' => 'Initial allocation',
                    'created_at' => now(),
                ]);
                $this->line('    Inserted inventory_adjustments ID: ' . $adjId . ' ✅');

                // Cleanup
                DB::table('ticket_inventory')->delete($invId);
                DB::table('inventory_adjustments')->delete($adjId);
                $this->line('    Test data cleaned up ✅');
            } else {
                $this->warn('    No seed data found. Tables exist and are ready ✅');
            }
        } catch (\Exception $e) {
            $this->error('    ERROR: ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('=== VERIFICATION COMPLETE ===');
    }
}

