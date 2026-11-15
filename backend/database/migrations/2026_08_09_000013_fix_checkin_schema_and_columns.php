<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->fixSqlite();
        } else {
            $this->fixMySql();
        }

        $this->addMissingColumns();
    }

    private function fixSqlite(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            DB::transaction(function () {
                Schema::dropIfExists('check_ins_new');

                $columns = DB::select('PRAGMA table_info(check_ins)');
                $fks = DB::select('PRAGMA foreign_key_list(check_ins)');

                $columnDefs = [];
                $columnNames = [];
                foreach ($columns as $col) {
                    $columnNames[] = $col->name;
                    $notNull = $col->notnull ? ' NOT NULL' : '';
                    $default = '';
                    if ($col->dflt_value !== null) {
                        $rawDefault = trim($col->dflt_value, "()'");
                        $default = $rawDefault !== '' ? " DEFAULT '$rawDefault'" : '';
                    }
                    $type = $col->type;
                    if ($col->name === 'event_id') {
                        $type = 'BIGINT UNSIGNED';
                    }
                    $columnDefs[] = "\"$col->name\" $type$notNull$default";
                }

                $primaryKey = 'PRIMARY KEY ("id")';

                $fkDefs = [];
                $fkMap = [
                    'ticket_id' => ['table' => 'tickets', 'column' => 'id', 'on_delete' => 'CASCADE'],
                    'user_id' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'SET NULL'],
                    'event_id' => ['table' => 'events', 'column' => 'id', 'on_delete' => 'CASCADE'],
                    'scanned_by' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'SET NULL'],
                ];
                foreach ($fkMap as $from => $ref) {
                    $fkDefs[] = "FOREIGN KEY (\"$from\") REFERENCES \"$ref[table]\"(\"$ref[column]\") ON DELETE $ref[on_delete]";
                }

                $createSql = sprintf(
                    'CREATE TABLE "check_ins_new" (%s, %s, %s)',
                    implode(', ', $columnDefs),
                    $primaryKey,
                    implode(', ', $fkDefs)
                );

                DB::statement($createSql);

                $columnList = implode('", "', $columnNames);
                DB::statement("INSERT INTO \"check_ins_new\" (\"$columnList\") SELECT \"$columnList\" FROM check_ins");

                $indexes = DB::select('PRAGMA index_list(check_ins)');

                DB::statement('DROP TRIGGER IF EXISTS prevent_event_delete_with_checkins');
                DB::statement('DROP TABLE check_ins');
                DB::statement('ALTER TABLE "check_ins_new" RENAME TO check_ins');

                foreach ($indexes as $idx) {
                    if (strpos($idx->name, 'sqlite_autoindex') !== 0) {
                        $cols = DB::select("PRAGMA index_info(\"$idx->name\")");
                        $colList = implode(', ', array_column($cols, 'name'));
                        if ($colList === '') {
                            continue;
                        }
                        if ($idx->unique) {
                            DB::statement("CREATE UNIQUE INDEX \"$idx->name\" ON check_ins ($colList)");
                        } else {
                            DB::statement("CREATE INDEX \"$idx->name\" ON check_ins ($colList)");
                        }
                    }
                }
            });
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function fixMySql(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
        });

        DB::statement('ALTER TABLE check_ins MODIFY COLUMN event_id BIGINT UNSIGNED NOT NULL');

        Schema::table('check_ins', function (Blueprint $table) {
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
        });
    }

    private function addMissingColumns(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            if (! Schema::hasColumn('check_ins', 'deleted_at')) {
                $table->softDeletes();
            }
            if (! Schema::hasColumn('check_ins', 'ticket_tier_id')) {
                $table->unsignedInteger('ticket_tier_id')->nullable()->after('scanned_by');
            }
            if (! Schema::hasColumn('check_ins', 'attendee_name')) {
                $table->string('attendee_name')->nullable()->after('ticket_tier_id');
            }
            if (! Schema::hasColumn('check_ins', 'attendee_email')) {
                $table->string('attendee_email')->nullable()->after('attendee_name');
            }
            if (! Schema::hasColumn('check_ins', 'gate_number')) {
                $table->string('gate_number')->nullable()->after('attendee_email');
            }
            if (! Schema::hasColumn('check_ins', 'scanned_by_name')) {
                $table->string('scanned_by_name')->nullable()->after('gate_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'ticket_tier_id',
                'attendee_name',
                'attendee_email',
                'gate_number',
                'scanned_by_name',
            ]);
        });

        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
            try {
                DB::transaction(function () {
                    Schema::dropIfExists('check_ins_new');
                    $columns = DB::select('PRAGMA table_info(check_ins)');
                    $columnList = implode('", "', array_column($columns, 'name'));
                    DB::statement("INSERT INTO \"check_ins_new\" (\"$columnList\") SELECT \"$columnList\" FROM check_ins");
                    DB::statement('DROP TABLE check_ins');
                    DB::statement('ALTER TABLE "check_ins_new" RENAME TO check_ins');
                });
            } finally {
                DB::statement('PRAGMA foreign_keys = ON');
            }
        } elseif ($driver === 'mysql') {
            Schema::table('check_ins', function (Blueprint $table) {
                $table->dropForeign(['event_id']);
            });
            DB::statement('ALTER TABLE check_ins MODIFY COLUMN event_id VARCHAR(36) NOT NULL');
            Schema::table('check_ins', function (Blueprint $table) {
                $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
            });
        }
    }
};
