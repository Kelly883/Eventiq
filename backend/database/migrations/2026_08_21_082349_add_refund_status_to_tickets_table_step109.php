<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        if (! Schema::hasColumn('tickets', 'refund_status')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->string('refund_status')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        if (Schema::hasColumn('tickets', 'refund_status')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropColumn('refund_status');
            });
        }
    }
};
