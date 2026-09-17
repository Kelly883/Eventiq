<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('organizers')) {
            return;
        }

        if (! Schema::hasColumn('organizers', 'paystack_subaccount_code')) {
            Schema::table('organizers', function (Blueprint $table) {
                $table->string('paystack_subaccount_code')->nullable();
            });
        }

        if (! Schema::hasColumn('organizers', 'flutterwave_subaccount_id')) {
            Schema::table('organizers', function (Blueprint $table) {
                $table->string('flutterwave_subaccount_id')->nullable();
            });
        }

        if (! Schema::hasColumn('organizers', 'paystack_connect_status')) {
            Schema::table('organizers', function (Blueprint $table) {
                $table->string('paystack_connect_status')->nullable();
            });
        }

        if (! Schema::hasColumn('organizers', 'flutterwave_connect_status')) {
            Schema::table('organizers', function (Blueprint $table) {
                $table->string('flutterwave_connect_status')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('organizers')) {
            return;
        }

        $columns = ['flutterwave_connect_status', 'paystack_connect_status', 'flutterwave_subaccount_id', 'paystack_subaccount_code'];

        $existing = [];
        foreach ($columns as $column) {
            if (Schema::hasColumn('organizers', $column)) {
                $existing[] = $column;
            }
        }

        if (! empty($existing)) {
            Schema::table('organizers', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }
};
