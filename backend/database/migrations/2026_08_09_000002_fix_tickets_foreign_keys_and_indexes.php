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
            try {
                $table->foreign('order_id', 'tickets_order_id_foreign')->references('id')->on('orders')->onDelete('cascade');
            } catch (\Exception $e) {
            }
            try {
                $table->foreign('user_id', 'tickets_user_id_foreign')->references('id')->on('users')->onDelete('cascade');
            } catch (\Exception $e) {
            }
            try {
                $table->foreign('event_id', 'tickets_event_id_foreign')->references('id')->on('events')->onDelete('cascade');
            } catch (\Exception $e) {
            }
            try {
                $table->foreign('ticket_tier_id', 'tickets_ticket_tier_id_foreign')->references('id')->on('ticket_tiers')->onDelete('cascade');
            } catch (\Exception $e) {
            }
            try {
                $table->foreign('checked_in_by')->references('id')->on('users')->onDelete('set null');
            } catch (\Exception $e) {
            }
        });

        Schema::table('tickets', function (Blueprint $table) {
            try {
                $table->index(['event_id', 'status'], 'idx_tickets_event_status');
            } catch (\Exception $e) {
            }
            try {
                $table->index(['event_id', 'checked_in_at'], 'idx_tickets_event_checkin');
            } catch (\Exception $e) {
            }
            try {
                $table->index(['event_id', 'created_at'], 'idx_tickets_event_created_at');
            } catch (\Exception $e) {
            }
            try {
                $table->index('ticket_id', 'idx_tickets_ticket_id_unique');
            } catch (\Exception $e) {
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
                try { $table->dropIndex($idx); } catch (\Exception $e) {}
            }

            $fks = [
                'tickets_order_id_foreign',
                'tickets_user_id_foreign',
                'tickets_event_id_foreign',
                'tickets_ticket_tier_id_foreign',
            ];
            foreach ($fks as $fk) {
                try { $table->dropForeign($fk); } catch (\Exception $e) {}
            }
        });
    }
};
