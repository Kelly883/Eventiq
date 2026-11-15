<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->addSqliteCheckConstraints();
        } elseif ($driver === 'mysql') {
            $this->convertMySqlEnums();
        }

        $this->fixEventDeleteTrigger();
        $this->dropDuplicateIndexes();
    }

    private function addSqliteCheckConstraints(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            $this->recreateCheckInsWithCheck();
            $this->recreatePaymentsWithCheck();
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function recreateCheckInsWithCheck(): void
    {
        Schema::dropIfExists('check_ins_new');

        $columns = DB::select('PRAGMA table_info(check_ins)');
        $fks = DB::select('PRAGMA foreign_key_list(check_ins)');
        $indexes = DB::select('PRAGMA index_list(check_ins)');

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
            if ($col->name === 'status') {
                $type = "VARCHAR(36) CHECK (status IN ('checked_in', 'failed', 'duplicate', 'expired'))";
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

        DB::transaction(function () use ($columnNames, $indexes) {
            $columnList = implode('", "', $columnNames);
            DB::statement("INSERT INTO \"check_ins_new\" (\"$columnList\") SELECT \"$columnList\" FROM check_ins");
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
    }

    private function recreatePaymentsWithCheck(): void
    {
        Schema::dropIfExists('payments_new');

        $columns = DB::select('PRAGMA table_info(payments)');
        $fks = DB::select('PRAGMA foreign_key_list(payments)');
        $indexes = DB::select('PRAGMA index_list(payments)');

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
            if ($col->name === 'status') {
                $type = "VARCHAR(50) CHECK (status IN ('pending', 'success', 'failed', 'cancelled', 'refunded'))";
            }
            $columnDefs[] = "\"$col->name\" $type$notNull$default";
        }

        $primaryKey = 'PRIMARY KEY ("id")';

        $fkDefs = [];
        $fkMap = [
            'order_id' => ['table' => 'orders', 'column' => 'id', 'on_delete' => 'CASCADE'],
            'refunded_by' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'SET NULL'],
        ];
        foreach ($fkMap as $from => $ref) {
            $fkDefs[] = "FOREIGN KEY (\"$from\") REFERENCES \"$ref[table]\"(\"$ref[column]\") ON DELETE $ref[on_delete]";
        }

        $createSql = sprintf(
            'CREATE TABLE "payments_new" (%s, %s, %s)',
            implode(', ', $columnDefs),
            $primaryKey,
            implode(', ', $fkDefs)
        );

        DB::statement($createSql);

        DB::transaction(function () use ($columnNames, $indexes) {
            $columnList = implode('", "', $columnNames);
            DB::statement("INSERT INTO \"payments_new\" (\"$columnList\") SELECT \"$columnList\" FROM payments");
            DB::statement('DROP TABLE payments');
            DB::statement('ALTER TABLE "payments_new" RENAME TO payments');

            foreach ($indexes as $idx) {
                if (strpos($idx->name, 'sqlite_autoindex') !== 0) {
                    $cols = DB::select("PRAGMA index_info(\"$idx->name\")");
                    $colList = implode(', ', array_column($cols, 'name'));
                    if ($colList === '') {
                        continue;
                    }
                    if ($idx->unique) {
                        DB::statement("CREATE UNIQUE INDEX \"$idx->name\" ON payments ($colList)");
                    } else {
                        DB::statement("CREATE INDEX \"$idx->name\" ON payments ($colList)");
                    }
                }
            }
        });
    }

    private function convertMySqlEnums(): void
    {
        try {
            DB::statement("
                ALTER TABLE check_ins
                MODIFY COLUMN status ENUM('checked_in', 'failed', 'duplicate', 'expired') NOT NULL DEFAULT 'checked_in'
            ");
        } catch (\Exception $e) {
        }

        try {
            DB::statement("
                ALTER TABLE payments
                MODIFY COLUMN status ENUM('pending', 'success', 'failed', 'cancelled', 'refunded') NOT NULL
            ");
        } catch (\Exception $e) {
        }
    }

    private function fixEventDeleteTrigger(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS prevent_event_delete_with_checkins');
            DB::statement("
                CREATE TRIGGER prevent_event_delete_with_checkins
                BEFORE DELETE ON events
                FOR EACH ROW
                BEGIN
                    SELECT RAISE(ABORT, 'Cannot delete event with existing check-ins, used tickets, or pending orders. Cancel the event instead.')
                    WHERE EXISTS (
                        SELECT 1 FROM tickets
                        WHERE event_id = OLD.id AND status IN ('checked_in', 'void', 'purged')
                    )
                    OR EXISTS (
                        SELECT 1 FROM check_ins
                        WHERE event_id = OLD.id
                    )
                    OR EXISTS (
                        SELECT 1 FROM orders
                        WHERE event_id = OLD.id AND status NOT IN ('refunded', 'failed')
                    );
                END
            ");
        } elseif ($driver === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS prevent_event_delete_with_checkins');
            DB::unprepared("
                CREATE TRIGGER prevent_event_delete_with_checkins
                BEFORE DELETE ON events
                FOR EACH ROW
                BEGIN
                    IF EXISTS (
                        SELECT 1 FROM tickets
                        WHERE event_id = OLD.id AND status IN ('checked_in', 'void', 'purged')
                    )
                    OR EXISTS (
                        SELECT 1 FROM check_ins
                        WHERE event_id = OLD.id
                    )
                    OR EXISTS (
                        SELECT 1 FROM orders
                        WHERE event_id = OLD.id AND status NOT IN ('refunded', 'failed')
                    ) THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Cannot delete event with existing check-ins, used tickets, or pending orders. Cancel the event instead.';
                    END IF;
                END
            ");
        } elseif ($driver === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS prevent_event_delete_with_checkins ON events');
            DB::unprepared("
                CREATE OR REPLACE FUNCTION prevent_event_delete_with_checkins_func()
                RETURNS trigger AS $$
                BEGIN
                    IF EXISTS (
                        SELECT 1 FROM tickets
                        WHERE event_id = OLD.id AND status IN ('checked_in', 'void', 'purged')
                    )
                    OR EXISTS (
                        SELECT 1 FROM check_ins
                        WHERE event_id = OLD.id
                    )
                    OR EXISTS (
                        SELECT 1 FROM orders
                        WHERE event_id = OLD.id AND status NOT IN ('refunded', 'failed')
                    ) THEN
                        RAISE EXCEPTION 'Cannot delete event with existing check-ins, used tickets, or pending orders. Cancel the event instead.';
                    END IF;
                    RETURN OLD;
                END;
                $$ LANGUAGE plpgsql;
            ");
            DB::unprepared("
                CREATE TRIGGER prevent_event_delete_with_checkins
                BEFORE DELETE ON events
                FOR EACH ROW
                EXECUTE FUNCTION prevent_event_delete_with_checkins_func();
            ");
        }
    }

    private function dropDuplicateIndexes(): void
    {
        $indexesToDrop = [
            'idx_payments_order_id',
            'idx_payments_payment_intent_id',
            'idx_orders_event_id',
            'order_items_order_id_index',
        ];

        $tables = [
            'idx_payments_order_id' => 'payments',
            'idx_payments_payment_intent_id' => 'payments',
            'idx_orders_event_id' => 'orders',
            'order_items_order_id_index' => 'order_items',
        ];

        foreach ($indexesToDrop as $index) {
            $table = $tables[$index] ?? null;
            if ($table && Schema::hasTable($table)) {
                try {
                    if (DB::getDriverName() === 'sqlite') {
                        DB::statement("DROP INDEX IF EXISTS \"$index\"");
                    } else {
                        DB::statement("DROP INDEX $index ON $table");
                    }
                } catch (\Exception $e) {
                }
            }
        }
    }

    public function down(): void
    {
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

            DB::statement('PRAGMA foreign_keys = OFF');
            try {
                DB::transaction(function () {
                    Schema::dropIfExists('payments_new');
                    $columns = DB::select('PRAGMA table_info(payments)');
                    $columnList = implode('", "', array_column($columns, 'name'));
                    DB::statement("INSERT INTO \"payments_new\" (\"$columnList\") SELECT \"$columnList\" FROM payments");
                    DB::statement('DROP TABLE payments');
                    DB::statement('ALTER TABLE "payments_new" RENAME TO payments');
                });
            } finally {
                DB::statement('PRAGMA foreign_keys = ON');
            }
        } elseif ($driver === 'mysql') {
            try {
                DB::statement("
                    ALTER TABLE check_ins
                    MODIFY COLUMN status VARCHAR(36) NOT NULL DEFAULT 'checked_in'
                ");
            } catch (\Exception $e) {
            }
            try {
                DB::statement("
                    ALTER TABLE payments
                    MODIFY COLUMN status VARCHAR(50) NOT NULL
                ");
            } catch (\Exception $e) {
            }
        }

        DB::statement('DROP TRIGGER IF EXISTS prevent_event_delete_with_checkins');
    }
};
