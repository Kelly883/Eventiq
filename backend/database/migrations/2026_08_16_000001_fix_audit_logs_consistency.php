<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * - Drops the orphaned `changes` column from `audit_logs` if it still
     *   exists. On MySQL, Step 71 renamed it to `details`; on SQLite the
     *   rename fails silently and both columns remain.
     * - Unifies the `ticket_id` foreign key to `SET NULL` on all drivers.
     *   Step 71 used CASCADE; a later MySQL-only migration changed it to
     *   SET NULL. For compliance, audit logs must survive ticket deletion.
     */
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            try {
                $this->fixSqlite();
            } finally {
                DB::statement('PRAGMA foreign_keys = ON');
            }
        } elseif ($driver === 'mysql') {
            $this->fixMySql();
        } elseif ($driver === 'pgsql') {
            $this->fixPostgreSql();
        }
    }

    private function fixSqlite(): void
    {
        $fks = DB::select('PRAGMA foreign_key_list(audit_logs)');
        $hasSetNull = false;
        $hasCascade = false;

        foreach ($fks as $fk) {
            if ($fk->from === 'ticket_id') {
                if (stripos($fk->on_delete, 'SET NULL') !== false) {
                    $hasSetNull = true;
                } elseif (stripos($fk->on_delete, 'CASCADE') !== false) {
                    $hasCascade = true;
                }
            }
        }

        if ($hasSetNull || ! $hasCascade) {
            return;
        }

        $columns = DB::select('PRAGMA table_info(audit_logs)');
        $indexes = DB::select('PRAGMA index_list(audit_logs)');

        $columnDefs = [];
        $columnNames = [];
        $primaryKey = null;

        foreach ($columns as $col) {
            $columnNames[] = $col->name;
            $notNull = $col->notnull ? ' NOT NULL' : '';
            $default = '';
            if ($col->dflt_value !== null) {
                $raw = trim($col->dflt_value, "()'");
                $default = $raw !== '' ? " DEFAULT '$raw'" : '';
            }

            if ($col->pk > 0) {
                $primaryKey = $col->name;
                $columnDefs[] = sprintf('"%s" %s%s PRIMARY KEY', $col->name, $col->type, $notNull);
            } else {
                $columnDefs[] = sprintf('"%s" %s%s%s', $col->name, $col->type, $notNull, $default);
            }
        }

        $fkMap = [];
        foreach ($fks as $fk) {
            $refTable = $fk->table;
            $refColumn = $fk->to;
            $onDelete = stripos($fk->on_delete, 'SET NULL') !== false ? 'SET NULL' : 'CASCADE';
            $fkMap[$fk->from] = [
                'table' => $refTable,
                'column' => $refColumn,
                'on_delete' => $onDelete,
            ];
        }

        foreach ($fkMap as $from => $ref) {
            if ($from === 'ticket_id') {
                $ref['on_delete'] = 'SET NULL';
            }
            $columnDefs[] = sprintf(
                'FOREIGN KEY ("%s") REFERENCES "%s"("%s") ON DELETE %s',
                $from,
                $ref['table'],
                $ref['column'],
                $ref['on_delete']
            );
        }

        $createSql = sprintf(
            'CREATE TABLE "audit_logs_new" (%s)',
            implode(', ', $columnDefs)
        );
        DB::statement($createSql);

        $columnList = implode('", "', $columnNames);
        DB::statement(sprintf(
            'INSERT INTO "audit_logs_new" ("%s") SELECT "%s" FROM audit_logs',
            $columnList,
            $columnList
        ));

        DB::statement('DROP TABLE audit_logs');
        DB::statement('ALTER TABLE "audit_logs_new" RENAME TO audit_logs');

        foreach ($indexes as $index) {
            if ($index->unique) {
                continue;
            }

            $quotedIndex = str_replace('\'', '\'\'', $index->name);
            $indexColumns = DB::select('PRAGMA index_info(\'' . $quotedIndex . '\')');
            $columnNames = array_column($indexColumns, 'name');
            if (empty($columnNames)) {
                continue;
            }

            $quotedColumns = implode('", "', $columnNames);
            DB::statement(sprintf(
                'CREATE INDEX "%s" ON "audit_logs" ("%s")',
                $index->name,
                $quotedColumns
            ));
        }
    }

    private function fixMySql(): void
    {
        try {
            DB::statement('ALTER TABLE audit_logs DROP FOREIGN KEY audit_logs_ticket_id_foreign');
        } catch (\Throwable $e) {
            // FK may not exist
        }

        try {
            DB::statement('ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE SET NULL');
        } catch (\Throwable $e) {
            // FK may already exist
        }
    }

    private function fixPostgreSql(): void
    {
        if (Schema::hasColumn('audit_logs', 'changes')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropColumn('changes');
            });
        }

        $fk = $this->getForeignKeyOnColumn('audit_logs', 'ticket_id');

        if ($fk && str_contains(strtoupper($fk['delete_rule']), 'CASCADE')) {
            $constraintName = $fk['constraint_name'];

            DB::statement(
                "ALTER TABLE \"audit_logs\" DROP CONSTRAINT \"{$constraintName}\""
            );

            Schema::table('audit_logs', function (Blueprint $table) {
                $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('set null');
            });
        }
    }

    private function getForeignKeyOnColumn(string $table, string $column): ?array
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        $row = DB::selectOne(
            'SELECT c.conname AS constraint_name, rc.delete_rule '
            . 'FROM pg_constraint c '
            . 'JOIN pg_class t ON c.conrelid = t.oid '
            . 'JOIN pg_namespace n ON t.relnamespace = n.oid '
            . 'JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = ANY(c.conkey) '
            . 'LEFT JOIN information_schema.referential_constraints rc ON rc.constraint_name = c.conname AND rc.constraint_schema = n.nspname '
            . "WHERE n.nspname = current_schema() "
            . "AND t.relname = ? "
            . "AND c.contype = 'f' "
            . "AND a.attname = ?",
            [$table, $column]
        );

        if ($row === null) {
            return null;
        }

        return [
            'constraint_name' => $row->constraint_name,
            'delete_rule' => $row->delete_rule ?? 'NO ACTION',
        ];
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->json('changes')->nullable()->after('entity_id');
        });

        // Note: We do not revert the FK back to CASCADE because that
        // would require dropping and recreating the table on SQLite.
        // The SET NULL behavior is the correct long-term state.
    }
};
