<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fraud_events')) {
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
        $fks = DB::select('PRAGMA foreign_key_list(fraud_events)');
        $existingFks = [];
        foreach ($fks as $fk) {
            $existingFks[$fk->from] = true;
        }

        $neededFks = [
            'order_id' => ['table' => 'orders', 'column' => 'id', 'on_delete' => 'CASCADE'],
            'user_id' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'CASCADE'],
            'ticket_id' => ['table' => 'tickets', 'column' => 'id', 'on_delete' => 'SET NULL'],
            'event_id' => ['table' => 'events', 'column' => 'id', 'on_delete' => 'SET NULL'],
            'first_check_in_by' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'SET NULL'],
            'second_check_in_by' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'SET NULL'],
            'reviewed_by' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'SET NULL'],
            'escalated_to' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'SET NULL'],
        ];

        $missingFks = [];
        foreach ($neededFks as $from => $ref) {
            if (! isset($existingFks[$from])) {
                $missingFks[$from] = $ref;
            }
        }

        if (empty($missingFks)) {
            return;
        }

        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            DB::transaction(function () use ($missingFks) {
                $columns = DB::select('PRAGMA table_info(fraud_events)');
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
                    $def = sprintf('"%s" %s%s%s', $col->name, $col->type, $notNull, $default);
                    $columnDefs[] = $def;
                }

                $fkDefs = [];
                foreach ($missingFks as $from => $ref) {
                    $fkDefs[] = sprintf(
                        'FOREIGN KEY ("%s") REFERENCES "%s"("%s") ON DELETE %s',
                        $from, $ref['table'], $ref['column'], $ref['on_delete']
                    );
                }

                $primaryKey = 'primary key ("id")';
                $createSql = sprintf(
                    'CREATE TABLE "fraud_events_new" (%s, %s, %s)',
                    implode(', ', $columnDefs),
                    $primaryKey,
                    implode(', ', $fkDefs)
                );
                DB::statement($createSql);

                $columnList = implode('", "', $columnNames);
                DB::statement(sprintf('INSERT INTO "fraud_events_new" ("%s") SELECT "%s" FROM fraud_events', $columnList, $columnList));

                DB::statement('DROP TABLE fraud_events');
                DB::statement('ALTER TABLE "fraud_events_new" RENAME TO fraud_events');
            });
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function fixMySql(): void
    {
        Schema::table('fraud_events', function (Blueprint $table) {
            $fks = [
                'order_id' => ['table' => 'orders', 'column' => 'id', 'on_delete' => 'cascade'],
                'user_id' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'cascade'],
                'ticket_id' => ['table' => 'tickets', 'column' => 'id', 'on_delete' => 'set null'],
                'event_id' => ['table' => 'events', 'column' => 'id', 'on_delete' => 'set null'],
                'first_check_in_by' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'set null'],
                'second_check_in_by' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'set null'],
                'reviewed_by' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'set null'],
                'escalated_to' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'set null'],
            ];

            foreach ($fks as $column => $ref) {
                try {
                    $table->foreign($column)->references('id')->on($ref['table'])->onDelete($ref['on_delete']);
                } catch (\Exception $e) {
                }
            }
        });
    }

    public function down(): void
    {
    }
};
