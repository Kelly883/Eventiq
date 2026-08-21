<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            return;
        }

        if (! Schema::hasColumn('payment_methods', 'gateway_payment_method_id')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->string('gateway_payment_method_id')->nullable()->after('gateway');
            });

            DB::table('payment_methods')->whereNull('gateway_payment_method_id')->update(['gateway_payment_method_id' => '']);

            Schema::table('payment_methods', function (Blueprint $table) {
                $table->string('gateway_payment_method_id')->nullable(false)->default('')->after('gateway')->change();
            });
        }

        try {
            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS idx_payment_methods_user_gateway_token ON payment_methods (user_id, gateway, gateway_payment_method_id) WHERE deleted_at IS NULL');
            } elseif ($driver === 'mysql') {
                DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS idx_payment_methods_user_gateway_token ON payment_methods (user_id, gateway, gateway_payment_method_id)');
            } elseif ($driver === 'pgsql') {
                DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS idx_payment_methods_user_gateway_token ON payment_methods (user_id, gateway, gateway_payment_method_id) WHERE deleted_at IS NULL');
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to create payment_methods unique index: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            return;
        }

        try {
            DB::statement('DROP INDEX IF EXISTS idx_payment_methods_user_gateway_token ON payment_methods');
        } catch (\Throwable $e) {
            // Index may not exist
        }
    }
};
