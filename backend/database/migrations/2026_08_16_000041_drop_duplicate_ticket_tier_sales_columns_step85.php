<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_tiers', function (Blueprint $table) {
            if (Schema::hasColumn('ticket_tiers', 'sales_start_at')) {
                $table->dropColumn('sales_start_at');
            }

            if (Schema::hasColumn('ticket_tiers', 'sales_end_at')) {
                $table->dropColumn('sales_end_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->dateTime('sales_start_at')->nullable()->after('sold_count');
            $table->dateTime('sales_end_at')->nullable()->after('sales_start_at');
        });
    }
};
