<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->fixSqlite();
        } else {
            $this->fixMySql();
        }
    }

    private function fixSqlite(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            DB::transaction(function () {
                $columns = DB::select('PRAGMA table_info(tickets)');
                $fks = DB::select('PRAGMA foreign_key_list(tickets)');
                $indexes = DB::select('PRAGMA index_list(tickets)');

                $columnDefs = [];
                $columnNames = [];
                $notNullCols = ['order_id', 'user_id', 'event_id', 'ticket_tier_id', 'status'];

                foreach ($columns as $col) {
                    $columnNames[] = $col->name;
                    $notNull = in_array($col->name, $notNullCols, true) ? ' not null' : '';
                    $rawDefault = $col->dflt_value;
                    if ($rawDefault !== null) {
                        $rawDefault = trim($rawDefault, "()'");
                        $default = $rawDefault !== '' ? " default '$rawDefault'" : '';
                    } else {
                        $default = '';
                    }

                    if ($col->name === 'status') {
                        $check = " check (\"status\" in ('valid', 'checked_in', 'void', 'purged'))";
                        $def = sprintf('"%s" varchar%s%s%s', $col->name, $notNull, $default, $check);
                    } else {
                        $def = sprintf('"%s" %s%s%s', $col->name, $col->type, $notNull, $default);
                    }
                    $columnDefs[] = $def;
                }

                $primaryKey = 'primary key ("id")';
                $createSql = sprintf('CREATE TABLE "tickets_new" (%s, %s)', implode(', ', $columnDefs), $primaryKey);
                DB::statement($createSql);

                $columnList = implode('", "', $columnNames);
                DB::statement(sprintf('INSERT INTO "tickets_new" ("%s") SELECT "%s" FROM tickets', $columnList, $columnList));

                $fkMap = [
                    'order_id' => ['table' => 'orders', 'column' => 'id', 'on_delete' => 'CASCADE'],
                    'user_id' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'CASCADE'],
                    'event_id' => ['table' => 'events', 'column' => 'id', 'on_delete' => 'CASCADE'],
                    'ticket_tier_id' => ['table' => 'ticket_tiers', 'column' => 'id', 'on_delete' => 'CASCADE'],
                    'checked_in_by' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'SET NULL'],
                ];

                foreach ($fkMap as $from => $ref) {
                    $sql = sprintf(
                        'ALTER TABLE "tickets_new" ADD CONSTRAINT "fk_tickets_new_%s" FOREIGN KEY ("%s") REFERENCES "%s"("%s") ON DELETE %s',
                        $from, $from, $ref['table'], $ref['column'], $ref['on_delete']
                    );
                    try {
                        DB::statement($sql);
                    } catch (\Exception $e) {
                        try {
                            $sql = sprintf(
                                'ALTER TABLE "tickets_new" ADD FOREIGN KEY ("%s") REFERENCES "%s"("%s") ON DELETE %s',
                                $from, $ref['table'], $ref['column'], $ref['on_delete']
                            );
                            DB::statement($sql);
                        } catch (\Exception $e2) {
                        }
                    }
                }

                $indexesToCreate = [
                    'tickets_user_id_index' => 'CREATE INDEX "tickets_user_id_index" ON "tickets_new" ("user_id")',
                    'tickets_event_id_index' => 'CREATE INDEX "tickets_event_id_index" ON "tickets_new" ("event_id")',
                    'idx_tickets_user_id' => 'CREATE INDEX "idx_tickets_user_id" ON "tickets_new" ("user_id")',
                    'idx_tickets_order_id' => 'CREATE INDEX "idx_tickets_order_id" ON "tickets_new" ("order_id")',
                    'idx_tickets_event_status' => 'CREATE INDEX "idx_tickets_event_status" ON "tickets_new" ("event_id", "status")',
                    'idx_tickets_event_checkin' => 'CREATE INDEX "idx_tickets_event_checkin" ON "tickets_new" ("event_id", "checked_in_at")',
                    'idx_tickets_event_created_at' => 'CREATE INDEX "idx_tickets_event_created_at" ON "tickets_new" ("event_id", "created_at")',
                    'idx_tickets_ticket_id_unique' => 'CREATE UNIQUE INDEX "idx_tickets_ticket_id_unique" ON "tickets_new" ("ticket_id")',
                ];

                foreach ($indexesToCreate as $name => $sql) {
                    try {
                        DB::statement($sql);
                    } catch (\Exception $e) {
                    }
                }

                DB::statement('DROP TABLE tickets');
                DB::statement('ALTER TABLE "tickets_new" RENAME TO tickets');
            });
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function fixMySql(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! $this->foreignKeyExists('tickets', 'order_id')) {
                $table->foreign('order_id', 'tickets_order_id_foreign')->references('id')->on('orders')->onDelete('cascade');
            }

            if (! $this->foreignKeyExists('tickets', 'user_id')) {
                $table->foreign('user_id', 'tickets_user_id_foreign')->references('id')->on('users')->onDelete('cascade');
            }

            if (! $this->foreignKeyExists('tickets', 'event_id')) {
                $table->foreign('event_id', 'tickets_event_id_foreign')->references('id')->on('events')->onDelete('cascade');
            }

            if (! $this->foreignKeyExists('tickets', 'ticket_tier_id')) {
                $table->foreign('ticket_tier_id', 'tickets_ticket_tier_id_foreign')->references('id')->on('ticket_tiers')->onDelete('cascade');
            }

            if (! $this->foreignKeyExists('tickets', 'checked_in_by')) {
                $table->foreign('checked_in_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        Schema::table('tickets', function (Blueprint $table) {
            if (! $this->indexExists('tickets', 'idx_tickets_event_status')) {
                $table->index(['event_id', 'status'], 'idx_tickets_event_status');
            }

            if (! $this->indexExists('tickets', 'idx_tickets_event_checkin')) {
                $table->index(['event_id', 'checked_in_at'], 'idx_tickets_event_checkin');
            }

            if (! $this->indexExists('tickets', 'idx_tickets_event_created_at')) {
                $table->index(['event_id', 'created_at'], 'idx_tickets_event_created_at');
            }

            if (! $this->indexExists('tickets', 'idx_tickets_ticket_id_unique')) {
                $table->index('ticket_id', 'idx_tickets_ticket_id_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $indexes = [
                'idx_tickets_event_status',
                'idx_tickets_event_checkin',
                'idx_tickets_event_created_at',
                'idx_tickets_ticket_id_unique',
            ];
            foreach ($indexes as $idx) {
                if ($this->indexExists('tickets', $idx)) {
                    $table->dropIndex($idx);
                }
            }

            $fks = [
                'tickets_order_id_foreign',
                'tickets_user_id_foreign',
                'tickets_event_id_foreign',
                'tickets_ticket_tier_id_foreign',
            ];
            foreach ($fks as $fk) {
                if ($this->foreignKeyExists('tickets', $fk)) {
                    $table->dropForeign($fk);
                }
            }
        });
    }

    private function foreignKeyExists(string $table, string $column): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        if (DB::getDriverName() === 'sqlite') {
            $rows = DB::select("PRAGMA foreign_key_list('{$table}')");

            foreach ($rows as $row) {
                if (($row->from ?? null) === $column) {
                    return true;
                }
            }

            return false;
        }

        if (DB::getDriverName() === 'pgsql') {
            $row = DB::selectOne(
                'SELECT c.conname FROM pg_constraint c '
                . 'JOIN pg_class t ON c.conrelid = t.oid '
                . 'JOIN pg_namespace n ON t.relnamespace = n.oid '
                . "WHERE n.nspname = current_schema() "
                . "AND t.relname = ? "
                . "AND c.contype = 'f' "
                . 'AND EXISTS ('
                . '  SELECT 1 FROM pg_attribute a '
                . '  WHERE a.attrelid = t.oid '
                . '  AND a.attname = ? '
                . '  AND a.attnum = ANY(c.conkey)'
                . ')',
                [$table, $column]
            );

            return $row !== null;
        }

        $row = DB::selectOne(
            'SELECT column_name FROM information_schema.key_column_usage WHERE table_schema = current_schema() AND table_name = ? AND column_name = ? AND referenced_table_name IS NOT NULL',
            [$table, $column]
        );

        return $row !== null;
    }

    private function indexExists(string $table, string $index): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        if (DB::getDriverName() === 'sqlite') {
            $rows = DB::select("PRAGMA index_list('{$table}')");

            foreach ($rows as $row) {
                if (($row->name ?? null) === $index) {
                    return true;
                }
            }

            return false;
        }

        if (DB::getDriverName() === 'pgsql') {
            $row = DB::selectOne(
                'SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?',
                [$table, $index]
            );

            return $row !== null;
        }

        $row = DB::selectOne(
            'SELECT index_name FROM information_schema.statistics WHERE table_schema = current_schema() AND table_name = ? AND index_name = ?',
            [$table, $index]
        );

        return $row !== null;
    }
};
