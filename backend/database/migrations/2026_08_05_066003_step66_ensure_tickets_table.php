<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        if (DB::getDriverName() === 'pgsql') {
            $row = DB::selectOne(
                'SELECT i.relname FROM pg_index x '
                    . 'JOIN pg_class i ON x.indexrelid = i.oid '
                    . 'JOIN pg_class t ON x.indrelid = t.oid '
                    . 'JOIN pg_namespace n ON t.relnamespace = n.oid '
                    . 'WHERE n.nspname = current_schema() '
                    . 'AND t.relname = ? '
                    . 'AND i.relname = ?',
                [$table, $indexName]
            );

            return $row !== null;
        }

        if (DB::getDriverName() === 'sqlite') {
            $rows = DB::select('PRAGMA index_list(' . $table . ')');

            foreach ($rows as $idx) {
                if ($idx->name === $indexName) {
                    return true;
                }
            }

            return false;
        }

        $row = DB::selectOne(
            'SELECT index_name FROM information_schema.statistics WHERE table_schema = current_schema() AND table_name = ? AND index_name = ?',
            [$table, $indexName]
        );

        return $row !== null;
    }

    public function up(): void
    {
        if (!Schema::hasTable('tickets')) {
            Schema::create('tickets', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('order_id');
                $table->uuid('user_id');
                $table->unsignedBigInteger('event_id');
                $table->unsignedBigInteger('ticket_tier_id');
                $table->text('qr_code_data')->nullable();
                $table->enum('status', ['valid', 'checked_in', 'void'])->default('valid');
                $table->timestamp('checked_in_at')->nullable();
                $table->timestamps();

                try { $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade'); } catch (\Throwable) {}
                try { $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade'); } catch (\Throwable) {}
                try { $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade'); } catch (\Throwable) {}
                try { $table->foreign('ticket_tier_id')->references('id')->on('ticket_tiers')->onDelete('cascade'); } catch (\Throwable) {}

                $table->index('user_id', 'tickets_user_id_index');
                $table->index('event_id', 'tickets_event_id_index');
            });

            return;
        }

        if (!Schema::hasColumn('tickets', 'order_id')) {
            try { Schema::table('tickets', fn (Blueprint $t) => $t->uuid('order_id')); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('tickets', 'user_id')) {
            try { Schema::table('tickets', fn (Blueprint $t) => $t->uuid('user_id')); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('tickets', 'event_id')) {
            try { Schema::table('tickets', fn (Blueprint $t) => $t->unsignedBigInteger('event_id')); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('tickets', 'ticket_tier_id')) {
            try { Schema::table('tickets', fn (Blueprint $t) => $t->unsignedBigInteger('ticket_tier_id')); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('tickets', 'qr_code_data')) {
            try { Schema::table('tickets', fn (Blueprint $t) => $t->text('qr_code_data')->nullable()); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('tickets', 'status')) {
            try { Schema::table('tickets', fn (Blueprint $t) => $t->enum('status', ['valid', 'checked_in', 'void'])->default('valid')); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('tickets', 'checked_in_at')) {
            try { Schema::table('tickets', fn (Blueprint $t) => $t->timestamp('checked_in_at')->nullable()); } catch (\Throwable) {}
        }
        if (!Schema::hasColumn('tickets', 'created_at') || !Schema::hasColumn('tickets', 'updated_at')) {
            try { Schema::table('tickets', fn (Blueprint $t) => $t->timestamps()); } catch (\Throwable) {}
        }

        if (!$this->indexExists('tickets', 'tickets_user_id_index')) {
            try { DB::statement('CREATE INDEX tickets_user_id_index ON tickets (user_id)'); } catch (\Throwable) {}
        }
        if (!$this->indexExists('tickets', 'tickets_event_id_index')) {
            try { DB::statement('CREATE INDEX tickets_event_id_index ON tickets (event_id)'); } catch (\Throwable) {}
        }

        try {
            Schema::table('tickets', function (Blueprint $table) {
                $table->foreign('order_id', 'tickets_order_id_foreign')
                    ->references('id')->on('orders')->onDelete('cascade');
            });
        } catch (\Throwable) {
        }
        try {
            Schema::table('tickets', function (Blueprint $table) {
                $table->foreign('user_id', 'tickets_user_id_foreign')
                    ->references('id')->on('users')->onDelete('cascade');
            });
        } catch (\Throwable) {
        }
        try {
            Schema::table('tickets', function (Blueprint $table) {
                $table->foreign('event_id', 'tickets_event_id_foreign')
                    ->references('id')->on('events')->onDelete('cascade');
            });
        } catch (\Throwable) {
        }
        try {
            Schema::table('tickets', function (Blueprint $table) {
                $table->foreign('ticket_tier_id', 'tickets_ticket_tier_id_foreign')
                    ->references('id')->on('ticket_tiers')->onDelete('cascade');
            });
        } catch (\Throwable) {
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
