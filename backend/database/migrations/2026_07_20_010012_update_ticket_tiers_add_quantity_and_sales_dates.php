<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_tiers', function (Blueprint $table) {
            if (!Schema::hasColumn('ticket_tiers', 'quantity')) {
                $table->unsignedInteger('quantity')->nullable(false)->after('price');
            }
            if (!Schema::hasColumn('ticket_tiers', 'sales_start_date')) {
                $table->dateTime('sales_start_date')->nullable()->after('quantity');
            }
            if (!Schema::hasColumn('ticket_tiers', 'sales_end_date')) {
                $table->dateTime('sales_end_date')->nullable()->after('sales_start_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ticket_tiers', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('ticket_tiers', 'quantity')) {
                $columns[] = 'quantity';
            }
            if (Schema::hasColumn('ticket_tiers', 'sales_start_date')) {
                $columns[] = 'sales_start_date';
            }
            if (Schema::hasColumn('ticket_tiers', 'sales_end_date')) {
                $columns[] = 'sales_end_date';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
