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
            Schema::table('ticket_inventory', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('ticket_inventory', 'total_available')) {
                    $columns[] = 'total_available';
                }
                if (Schema::hasColumn('ticket_inventory', 'is_low_stock')) {
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
