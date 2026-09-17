<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('status')->default('active');
            });
        }

        if (Schema::hasTable('payments')) {
            if (! Schema::hasColumn('payments', 'payment_method')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->string('payment_method')->nullable();
                });
            }
            if (! Schema::hasColumn('payments', 'gateway_response_code')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->string('gateway_response_code')->nullable();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('idx_users_role_status_created_at');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        if (Schema::hasTable('payments')) {
            if (Schema::hasColumn('payments', 'payment_method')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->dropColumn('payment_method');
                });
            }
            if (Schema::hasColumn('payments', 'gateway_response_code')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->dropColumn('gateway_response_code');
                });
            }
        }
    }
};
