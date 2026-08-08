<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('valid', 'checked_in', 'void', 'purged') DEFAULT 'valid'");
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildTicketsTable(['valid', 'checked_in', 'void', 'purged']);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('valid', 'checked_in', 'void') DEFAULT 'valid'");
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildTicketsTable(['valid', 'checked_in', 'void']);
        }
    }

    private function rebuildTicketsTable(array $allowedStatuses): void
    {
        $newTable = 'tickets_new';
        $columns = DB::select('PRAGMA table_info(tickets)');
        $fks = DB::select('PRAGMA foreign_key_list(tickets)');
        $columnDefs = [];
        $columnNames = [];

        foreach ($columns as $col) {
            $columnNames[] = $col->name;
            $notNull = $col->notnull ? ' not null' : '';
            $rawDefault = $col->dflt_value;
            if ($rawDefault !== null) {
                $rawDefault = trim($rawDefault, "()'");
                $default = $rawDefault !== '' ? " default '$rawDefault'" : '';
            } else {
                $default = '';
            }

            if ($col->name === 'status') {
                $check = sprintf(' check ("status" in (%s))', implode(', ', array_map(fn($s) => "'$s'", $allowedStatuses)));
                $def = sprintf('"%s" varchar%s%s%s', $col->name, $notNull, $default, $check);
            } else {
                $def = sprintf('"%s" %s%s%s', $col->name, $col->type, $notNull, $default);
            }
            $columnDefs[] = $def;
        }

        $primaryKey = 'primary key ("id")';
        $createSql = sprintf('CREATE TABLE "%s" (%s, %s)', $newTable, implode(', ', $columnDefs), $primaryKey);
        DB::statement($createSql);

        $columnList = implode('", "', $columnNames);
        DB::statement(sprintf('INSERT INTO "%s" ("%s") SELECT "%s" FROM tickets', $newTable, $columnList, $columnList));

        foreach ($fks as $fk) {
            $onDelete = strtoupper($fk->on_delete);
            $onUpdate = strtoupper($fk->on_update);
            $sql = sprintf(
                'ALTER TABLE "%s" ADD CONSTRAINT "fk_%s_%s" FOREIGN KEY ("%s") REFERENCES "%s"("%s") ON DELETE %s ON UPDATE %s',
                $newTable,
                $newTable,
                $fk->from,
                $fk->from,
                $fk->table,
                $fk->to,
                $onDelete === 'NO ACTION' ? 'NO ACTION' : $onDelete,
                $onUpdate === 'NO ACTION' ? 'NO ACTION' : $onUpdate
            );
            try {
                DB::statement($sql);
            } catch (\Exception $e) {
                // SQLite may reject named constraints; try without name
                try {
                    $sql = sprintf(
                        'ALTER TABLE "%s" ADD FOREIGN KEY ("%s") REFERENCES "%s"("%s") ON DELETE %s ON UPDATE %s',
                        $newTable,
                        $fk->from,
                        $fk->table,
                        $fk->to,
                        $onDelete === 'NO ACTION' ? 'NO ACTION' : $onDelete,
                        $onUpdate === 'NO ACTION' ? 'NO ACTION' : $onUpdate
                    );
                    DB::statement($sql);
                } catch (\Exception $e2) {
                    // ignore
                }
            }
        }

        DB::statement('DROP TABLE tickets');
        DB::statement(sprintf('ALTER TABLE "%s" RENAME TO tickets', $newTable));
    }
};
