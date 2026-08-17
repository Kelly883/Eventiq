<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_windows', function (Blueprint $table) {
            // Only add check constraints on databases that support them well.
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->check('quantity_sold_non_negative', DB::raw('quantity_sold >= 0'));
                $table->check('price_non_negative', DB::raw('price >= 0'));
                $table->check('priority_non_negative', DB::raw('priority >= 0'));
            }
        });
    }

    public function down(): void
    {
        Schema::table('pricing_windows', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->dropCheck('quantity_sold_non_negative');
                $table->dropCheck('price_non_negative');
                $table->dropCheck('priority_non_negative');
            }
        });
    }
};
