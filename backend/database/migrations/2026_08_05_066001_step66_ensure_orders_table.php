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
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id')->nullable();
                $table->unsignedBigInteger('event_id')->nullable();
                $table->decimal('total_amount', 10, 2);
                $table->string('currency', 3)->default('NGN');
                $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
                $table->string('payment_gateway')->nullable();
                $table->string('payment_intent_id')->nullable();
                $table->timestamps();

                try { $table->foreign('user_id')->references('id')->on('users')->onDelete('set null'); } catch (\Throwable) {}
                try { $table->foreign('event_id')->references('id')->on('events')->onDelete('set null'); } catch (\Throwable) {}

                $table->index('user_id', 'orders_user_id_index');
                $table->index('status', 'orders_status_index');
                try { $table->unique('payment_intent_id', 'orders_payment_intent_id_unique'); } catch (\Throwable) {}
            });

            return;
        }

        if (!Schema::hasColumn('orders', 'user_id')) {
            try { Schema::table('orders', fn (Blueprint $t) => $t->uuid('user_id')->nullable()); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('orders', 'event_id')) {
            try { Schema::table('orders', fn (Blueprint $t) => $t->unsignedBigInteger('event_id')->nullable()); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('orders', 'total_amount')) {
            try { Schema::table('orders', fn (Blueprint $t) => $t->decimal('total_amount', 10, 2)); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('orders', 'currency')) {
            try { Schema::table('orders', fn (Blueprint $t) => $t->string('currency', 3)->default('NGN')); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('orders', 'status')) {
            try { Schema::table('orders', fn (Blueprint $t) => $t->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending')); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('orders', 'payment_gateway')) {
            try { Schema::table('orders', fn (Blueprint $t) => $t->string('payment_gateway')->nullable()); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('orders', 'payment_intent_id')) {
            try { Schema::table('orders', fn (Blueprint $t) => $t->string('payment_intent_id')->nullable()); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('orders', 'created_at') || !Schema::hasColumn('orders', 'updated_at')) {
            try { Schema::table('orders', fn (Blueprint $t) => $t->timestamps()); } catch (\Throwable) {}
        }

        if (!$this->indexExists('orders', 'orders_user_id_index')) {
            try { DB::statement('CREATE INDEX orders_user_id_index ON orders (user_id)'); } catch (\Throwable) {}
        }
        if (!$this->indexExists('orders', 'orders_status_index')) {
            try { DB::statement('CREATE INDEX orders_status_index ON orders (status)'); } catch (\Throwable) {}
        }
        if (!$this->indexExists('orders', 'orders_payment_intent_id_unique')) {
            try { DB::statement('CREATE UNIQUE INDEX orders_payment_intent_id_unique ON orders (payment_intent_id)'); } catch (\Throwable) {}
        }

        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('user_id', 'orders_user_id_foreign')
                    ->references('id')->on('users')->onDelete('set null');
            });
        } catch (\Throwable) {
        }
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('event_id', 'orders_event_id_foreign')
                    ->references('id')->on('events')->onDelete('set null');
            });
        } catch (\Throwable) {
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
