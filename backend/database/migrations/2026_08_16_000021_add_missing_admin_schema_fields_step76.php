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
        if (Schema::hasTable('users') && ! $this->hasColumn('users', 'status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('status')->default('active')->after('role');
            });
        }

        if (Schema::hasTable('payments')) {
            if (! $this->hasColumn('payments', 'payment_method')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->string('payment_method')->nullable()->after('gateway');
                });
            }
            if (! $this->hasColumn('payments', 'gateway_response_code')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->string('gateway_response_code')->nullable()->after('gateway_response');
                });
            }
        }

        // Note: ticket_inventory.is_low_stock already exists as a SQLite
        // generated column computed from total_available and low_stock_threshold.
        // No migration needed.
    }

    private function hasColumn(string $table, string $column): bool
    {
        $columns = DB::select('PRAGMA table_info(' . $table . ')');
        foreach ($columns as $col) {
            if ($col->name === $column) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && $this->hasColumn('users', 'status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('idx_users_role_status_created_at');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        if (Schema::hasTable('payments')) {
            if ($this->hasColumn('payments', 'payment_method')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->dropColumn('payment_method');
                });
            }
            if ($this->hasColumn('payments', 'gateway_response_code')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->dropColumn('gateway_response_code');
                });
            }
        }
    }
};
