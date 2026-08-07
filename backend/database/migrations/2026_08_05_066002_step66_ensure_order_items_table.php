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
        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('order_id');
                $table->unsignedBigInteger('ticket_tier_id');
                $table->integer('quantity');
                $table->decimal('unit_price', 10, 2);
                $table->timestamps();

                try { $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade'); } catch (\Throwable) {}
                try { $table->foreign('ticket_tier_id')->references('id')->on('ticket_tiers')->onDelete('cascade'); } catch (\Throwable) {}

                $table->index('order_id', 'order_items_order_id_index');
            });

            return;
        }

        if (!Schema::hasColumn('order_items', 'order_id')) {
            try { Schema::table('order_items', fn (Blueprint $t) => $t->uuid('order_id')); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('order_items', 'ticket_tier_id')) {
            try { Schema::table('order_items', fn (Blueprint $t) => $t->unsignedBigInteger('ticket_tier_id')); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('order_items', 'quantity')) {
            try { Schema::table('order_items', fn (Blueprint $t) => $t->integer('quantity')); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('order_items', 'unit_price')) {
            try { Schema::table('order_items', fn (Blueprint $t) => $t->decimal('unit_price', 10, 2)); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('order_items', 'created_at') || !Schema::hasColumn('order_items', 'updated_at')) {
            try { Schema::table('order_items', fn (Blueprint $t) => $t->timestamps()); } catch (\Throwable) {}
        }

        if (!$this->indexExists('order_items', 'order_items_order_id_index')) {
            try { DB::statement('CREATE INDEX order_items_order_id_index ON order_items (order_id)'); } catch (\Throwable) {}
        }

        try {
            Schema::table('order_items', function (Blueprint $table) {
                $table->foreign('order_id', 'order_items_order_id_foreign')
                    ->references('id')->on('orders')->onDelete('cascade');
            });
        } catch (\Throwable) {
        }
        try {
            Schema::table('order_items', function (Blueprint $table) {
                $table->foreign('ticket_tier_id', 'order_items_ticket_tier_id_foreign')
                    ->references('id')->on('ticket_tiers')->onDelete('cascade');
            });
        } catch (\Throwable) {
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
