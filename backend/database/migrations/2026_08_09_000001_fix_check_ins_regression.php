<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('check_ins')) {
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
                $columns = DB::select('PRAGMA table_info(check_ins)');
                $fks = DB::select('PRAGMA foreign_key_list(check_ins)');
                $indexes = DB::select('PRAGMA index_list(check_ins)');

                $columnDefs = [];
                $columnNames = [];
                $notNullCols = ['ticket_id', 'checked_in_at'];

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
                        $check = " check (\"status\" in ('checked_in', 'failed', 'duplicate', 'expired'))";
                        $def = sprintf('"%s" varchar%s%s%s', $col->name, $notNull, $default, $check);
                    } else {
                        $def = sprintf('"%s" %s%s%s', $col->name, $col->type, $notNull, $default);
                    }
                    $columnDefs[] = $def;
                }

                $newColumns = [
                    'event_id integer not null',
                    'scanned_by varchar',
                    'status varchar not null default \'checked_in\'',
                    'device_type varchar',
                    'device_id varchar',
                    'ip_address varchar',
                    'user_agent varchar',
                    'qr_verified tinyint(1) not null default 1',
                    'failure_reason text',
                ];

                foreach ($newColumns as $def) {
                    if (! in_array(explode(' ', $def)[0], $columnNames, true)) {
                        $columnDefs[] = $def;
                        $columnNames[] = explode(' ', $def)[0];
                    }
                }

                $primaryKey = 'primary key ("id")';
                $createSql = sprintf('CREATE TABLE "check_ins_new" (%s, %s)', implode(', ', $columnDefs), $primaryKey);
                DB::statement($createSql);

                $columnList = implode('", "', $columnNames);
                DB::statement(sprintf('INSERT INTO "check_ins_new" ("%s") SELECT "%s" FROM check_ins', $columnList, $columnList));

                $fkMap = [
                    'ticket_id' => ['table' => 'tickets', 'column' => 'id', 'on_delete' => 'CASCADE'],
                    'user_id' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'SET NULL'],
                    'event_id' => ['table' => 'events', 'column' => 'id', 'on_delete' => 'CASCADE'],
                    'scanned_by' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'SET NULL'],
                ];

                foreach ($fks as $fk) {
                    $from = $fk->from;
                    if (isset($fkMap[$from])) {
                        $ref = $fkMap[$from];
                        $sql = sprintf(
                            'ALTER TABLE "check_ins_new" ADD CONSTRAINT "fk_check_ins_new_%s" FOREIGN KEY ("%s") REFERENCES "%s"("%s") ON DELETE %s',
                            $from, $from, $ref['table'], $ref['column'], $ref['on_delete']
                        );
                        try {
                            DB::statement($sql);
                        } catch (\Exception $e) {
                            try {
                                $sql = sprintf(
                                    'ALTER TABLE "check_ins_new" ADD FOREIGN KEY ("%s") REFERENCES "%s"("%s") ON DELETE %s',
                                    $from, $ref['table'], $ref['column'], $ref['on_delete']
                                );
                                DB::statement($sql);
                            } catch (\Exception $e2) {
                            }
                        }
                    }
                }

                $indexesToCreate = [
                    'idx_checkins_event_scanned' => 'CREATE UNIQUE INDEX "idx_checkins_event_scanned" ON "check_ins_new" ("event_id", "checked_in_at")',
                    'idx_checkins_ticket_scanned' => 'CREATE UNIQUE INDEX "idx_checkins_ticket_scanned" ON "check_ins_new" ("ticket_id", "checked_in_at")',
                    'idx_checkins_scanned_by' => 'CREATE INDEX "idx_checkins_scanned_by" ON "check_ins_new" ("scanned_by")',
                    'idx_checkins_scanned_at' => 'CREATE INDEX "idx_checkins_scanned_at" ON "check_ins_new" ("checked_in_at")',
                    'check_ins_user_id_index' => 'CREATE INDEX "check_ins_user_id_index" ON "check_ins_new" ("user_id")',
                    'check_ins_ticket_id_index' => 'CREATE INDEX "check_ins_ticket_id_index" ON "check_ins_new" ("ticket_id")',
                ];

                foreach ($indexesToCreate as $name => $sql) {
                    try {
                        DB::statement($sql);
                    } catch (\Exception $e) {
                    }
                }

                DB::statement('DROP TABLE check_ins');
                DB::statement('ALTER TABLE "check_ins_new" RENAME TO check_ins');
            });
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function fixMySql(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            if (! Schema::hasColumn('check_ins', 'event_id')) {
                $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('check_ins', 'scanned_by')) {
                $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('check_ins', 'status')) {
                $table->string('status')->default('checked_in')->after('user_id');
            }
            if (! Schema::hasColumn('check_ins', 'device_type')) {
                $table->string('device_type')->nullable()->after('status');
            }
            if (! Schema::hasColumn('check_ins', 'device_id')) {
                $table->string('device_id')->nullable()->after('device_type');
            }
            if (! Schema::hasColumn('check_ins', 'ip_address')) {
                $table->string('ip_address')->nullable()->after('device_id');
            }
            if (! Schema::hasColumn('check_ins', 'user_agent')) {
                $table->string('user_agent')->nullable()->after('ip_address');
            }
            if (! Schema::hasColumn('check_ins', 'qr_verified')) {
                $table->boolean('qr_verified')->default(true)->after('user_agent');
            }
            if (! Schema::hasColumn('check_ins', 'failure_reason')) {
                $table->text('failure_reason')->nullable()->after('qr_verified');
            }
        });

        Schema::table('check_ins', function (Blueprint $table) {
            try {
                $table->index(['event_id', 'checked_in_at'], 'idx_checkins_event_scanned');
            } catch (\Exception $e) {
            }
            try {
                $table->unique(['ticket_id', 'checked_in_at'], 'uq_checkins_ticket_scanned');
            } catch (\Exception $e) {
            }
            try {
                $table->index('scanned_by', 'idx_checkins_scanned_by');
            } catch (\Exception $e) {
            }
            try {
                $table->index('checked_in_at', 'idx_checkins_scanned_at');
            } catch (\Exception $e) {
            }
        });
    }

    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            $columns = ['event_id', 'scanned_by', 'status', 'device_type', 'device_id', 'ip_address', 'user_agent', 'qr_verified', 'failure_reason'];
            $existing = [];
            foreach ($columns as $col) {
                if (Schema::hasColumn('check_ins', $col)) {
                    $existing[] = $col;
                }
            }
            if (! empty($existing)) {
                $table->dropColumn($existing);
            }

            try { $table->dropIndex('idx_checkins_event_scanned'); } catch (\Exception $e) {}
            try { $table->dropUnique('uq_checkins_ticket_scanned'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_checkins_scanned_by'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_checkins_scanned_at'); } catch (\Exception $e) {}
        });
    }
};
