<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_inventory', function (Blueprint $table) {
            if (Schema::hasColumn('ticket_inventory', 'total_available')) {
                $table->dropColumn('total_available');
            }

            if (Schema::hasColumn('ticket_inventory', 'is_low_stock')) {
                $table->dropColumn('is_low_stock');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ticket_inventory', function (Blueprint $table) {
            $table->integer('total_available')->virtualAs('total_allocated - total_sold');
            $table->boolean('is_low_stock')->virtualAs(
                "CASE WHEN total_available > 0 AND total_available <= COALESCE(low_stock_threshold, 0) THEN 1 ELSE 0 END"
            );
        });
    }
};
