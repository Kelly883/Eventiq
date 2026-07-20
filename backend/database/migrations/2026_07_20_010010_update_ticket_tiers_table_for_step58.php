<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->renameColumn('capacity', 'quantity');
            $table->dateTime('sales_start_date')->nullable()->after('quantity');
            $table->dateTime('sales_end_date')->nullable()->after('sales_start_date');

            $table->dropColumn('benefits');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->renameColumn('quantity', 'capacity');

            $table->json('benefits')->nullable()->after('early_bird_end_date');

            $table->dropColumn(['sales_start_date', 'sales_end_date']);
        });
    }
};