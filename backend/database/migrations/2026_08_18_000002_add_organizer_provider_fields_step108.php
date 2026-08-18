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

        if (! Schema::hasColumn('organizers', 'paystack_business_name')) {
            Schema::table('organizers', function (Blueprint $table) {
                $table->string('paystack_business_name')->nullable()->after('paystack_subaccount_code');
            });
        }

        if (! Schema::hasColumn('organizers', 'paystack_recipient_code')) {
            Schema::table('organizers', function (Blueprint $table) {
                $table->string('paystack_recipient_code')->nullable()->after('paystack_business_name');
            });
        }

        if (! Schema::hasColumn('organizers', 'flutterwave_business_reference')) {
            Schema::table('organizers', function (Blueprint $table) {
                $table->string('flutterwave_business_reference')->nullable()->after('flutterwave_subaccount_id');
            });
        }

        if (! Schema::hasColumn('organizers', 'paystack_connected_at')) {
            Schema::table('organizers', function (Blueprint $table) {
                $table->timestamp('paystack_connected_at')->nullable()->after('paystack_connect_status');
            });
        }

        if (! Schema::hasColumn('organizers', 'flutterwave_connected_at')) {
            Schema::table('organizers', function (Blueprint $table) {
                $table->timestamp('flutterwave_connected_at')->nullable()->after('flutterwave_connect_status');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('organizers')) {
            return;
        }

        $columns = [
            'flutterwave_connected_at',
            'paystack_connected_at',
            'flutterwave_business_reference',
            'paystack_recipient_code',
            'paystack_business_name',
        ];

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
