<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->dateTime('sales_start_at')->nullable()->after('sold_count');
            $table->dateTime('sales_end_at')->nullable()->after('sales_start_at');
            $table->boolean('is_visible')->default(true)->after('sales_end_at');
            $table->boolean('is_sold_out')->default(false)->after('is_visible');
            $table->boolean('allow_repurchase')->default(true)->after('is_sold_out');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->dropColumn([
                'allow_repurchase',
                'is_sold_out',
                'is_visible',
                'sales_end_at',
                'sales_start_at',
                'sold_count',
            ]);
        });
    }
};