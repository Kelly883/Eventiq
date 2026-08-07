<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();
        $name = strtolower($indexName);
        try {
            if ($driver === 'sqlite') {
                $rows = DB::select("PRAGMA index_list(`{$table}`)");
                foreach ($rows as $r) {
                    if (strtolower($r->name) === $name) {
                        return true;
                    }
                }
            } else {
                $rows = DB::select("SHOW INDEX FROM `{$table}`");
                foreach ($rows as $r) {
                    if (strtolower($r->Key_name) === $name) {
                        return true;
                    }
                }
            }
        } catch (\Throwable) {
            return false;
        }
        return false;
    }

    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('order_id');
                $table->string('payment_intent_id')->nullable();
                $table->decimal('amount', 10, 2);
                $table->string('currency', 3)->default('NGN');
                $table->string('status');
                $table->string('gateway');
                $table->json('gateway_response')->nullable();
                $table->timestamps();

                try { $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade'); } catch (\Throwable) {}

                $table->index('order_id', 'payments_order_id_index');
            });

            return;
        }

        if (!Schema::hasColumn('payments', 'order_id')) {
            try { Schema::table('payments', fn (Blueprint $t) => $t->uuid('order_id')); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('payments', 'payment_intent_id')) {
            try { Schema::table('payments', fn (Blueprint $t) => $t->string('payment_intent_id')->nullable()); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('payments', 'amount')) {
            try { Schema::table('payments', fn (Blueprint $t) => $t->decimal('amount', 10, 2)); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('payments', 'currency')) {
            try { Schema::table('payments', fn (Blueprint $t) => $t->string('currency', 3)->default('NGN')); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('payments', 'status')) {
            try { Schema::table('payments', fn (Blueprint $t) => $t->string('status')); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('payments', 'gateway')) {
            try { Schema::table('payments', fn (Blueprint $t) => $t->string('gateway')); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('payments', 'gateway_response')) {
            try { Schema::table('payments', fn (Blueprint $t) => $t->json('gateway_response')->nullable()); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('payments', 'created_at') || !Schema::hasColumn('payments', 'updated_at')) {
            try { Schema::table('payments', fn (Blueprint $t) => $t->timestamps()); } catch (\Throwable) {}
        }

        if (!$this->indexExists('payments', 'payments_order_id_index')) {
            try { DB::statement('CREATE INDEX payments_order_id_index ON payments (order_id)'); } catch (\Throwable) {}
        }

        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreign('order_id', 'payments_order_id_foreign')
                    ->references('id')->on('orders')->onDelete('cascade');
            });
        } catch (\Throwable) {
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
