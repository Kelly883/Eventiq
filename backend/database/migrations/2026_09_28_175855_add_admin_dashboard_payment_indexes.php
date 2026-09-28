<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'payments_status_created_at_index');
            $table->index(['status', 'paid_at'], 'payments_status_paid_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_status_created_at_index');
            $table->dropIndex('payments_status_paid_at_index');
        });
    }
};
