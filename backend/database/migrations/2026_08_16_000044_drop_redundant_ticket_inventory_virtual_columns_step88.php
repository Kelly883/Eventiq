<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (config('database.default') === 'sqlite') {
            return;
        }

        $hasTotalAvailable = Schema::hasColumn('ticket_inventory', 'total_available');
        $hasIsLowStock = Schema::hasColumn('ticket_inventory', 'is_low_stock');

        if ($hasTotalAvailable || $hasIsLowStock) {
            $hasTicketInventoryTotalAvailable = Schema::hasColumn('ticket_inventory', 'total_available');
            $hasTicketInventoryIsLowStock = Schema::hasColumn('ticket_inventory', 'is_low_stock');
            Schema::table('ticket_inventory', function (Blueprint $table) use ($hasTicketInventoryTotalAvailable, $hasTicketInventoryIsLowStock) {
                $columns = [];
                if ($hasTicketInventoryTotalAvailable) {
                    $columns[] = 'total_available';
                }
                if ($hasTicketInventoryIsLowStock) {
                    $columns[] = 'is_low_stock';
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('ticket_inventory', function (Blueprint $table) {
            $table->integer('total_available')->storedAs('total_allocated - total_sold');
            $table->boolean('is_low_stock')->storedAs(
                "CASE WHEN (total_allocated - total_sold) > 0 AND (total_allocated - total_sold) <= COALESCE(low_stock_threshold, 0) THEN TRUE ELSE FALSE END"
            );
        });
    }
};
