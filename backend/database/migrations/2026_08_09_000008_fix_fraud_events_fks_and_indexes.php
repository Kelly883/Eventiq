<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        } elseif ($driver === 'mysql') {
            $this->fixMySql();
        } elseif ($driver === 'pgsql') {
            $this->fixPostgreSql();
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

        if (! empty($missingFks)) {
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

                    $primaryKey = 'primary key ("id")';
                    $createSql = sprintf('CREATE TABLE "fraud_events_new" (%s, %s)', implode(', ', $columnDefs), $primaryKey);
                    DB::statement($createSql);

                    $columnList = implode('", "', $columnNames);
                    DB::statement(sprintf('INSERT INTO "fraud_events_new" ("%s") SELECT "%s" FROM fraud_events', $columnList, $columnList));

                    foreach ($missingFks as $from => $ref) {
                        $sql = sprintf(
                            'ALTER TABLE "fraud_events_new" ADD CONSTRAINT "fk_fraud_events_new_%s" FOREIGN KEY ("%s") REFERENCES "%s"("%s") ON DELETE %s',
                            $from, $from, $ref['table'], $ref['column'], $ref['on_delete']
                        );
                        try {
                            DB::statement($sql);
                        } catch (\Exception $e) {
                            try {
                                $sql = sprintf(
                                    'ALTER TABLE "fraud_events_new" ADD FOREIGN KEY ("%s") REFERENCES "%s"("%s") ON DELETE %s',
                                    $from, $ref['table'], $ref['column'], $ref['on_delete']
                                );
                                DB::statement($sql);
                            } catch (\Exception $e2) {
                            }
                        }
                    }

                    DB::statement('DROP TABLE fraud_events');
                    DB::statement('ALTER TABLE "fraud_events_new" RENAME TO fraud_events');
                });
            } finally {
                DB::statement('PRAGMA foreign_keys = ON');
            }
        }

        $indexesToKeep = [
            'idx_fraud_user_created',
            'idx_fraud_status_created',
            'idx_fraud_risk_created',
            'idx_fraud_order_type_unique',
            'idx_fraud_event_detected',
            'idx_fraud_reviewer_status',
            'idx_fraud_created_at',
            'idx_fraud_ip_address',
            'idx_fraud_card_fingerprint',
        ];

        $indexes = DB::select('PRAGMA index_list(fraud_events)');
        foreach ($indexes as $idx) {
            if (! in_array($idx->name, $indexesToKeep, true)) {
                try {
                    DB::statement("DROP INDEX \"{$idx->name}\"");
                } catch (\Exception $e) {
                }
            }
        }
    }

    private function fixMySql(): void
    {
        Schema::table('fraud_events', function (Blueprint $table) {
            if (! $this->foreignKeyExists('fraud_events', 'order_id')) {
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            }

            if (! $this->foreignKeyExists('fraud_events', 'user_id')) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }

            if (! $this->foreignKeyExists('fraud_events', 'ticket_id')) {
                $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('set null');
            }

            if (! $this->foreignKeyExists('fraud_events', 'event_id')) {
                $table->foreign('event_id')->references('id')->on('events')->onDelete('set null');
            }

            if (! $this->foreignKeyExists('fraud_events', 'first_check_in_by')) {
                $table->foreign('first_check_in_by')->references('id')->on('users')->onDelete('set null');
            }

            if (! $this->foreignKeyExists('fraud_events', 'second_check_in_by')) {
                $table->foreign('second_check_in_by')->references('id')->on('users')->onDelete('set null');
            }

            if (! $this->foreignKeyExists('fraud_events', 'reviewed_by')) {
                $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            }

            if (! $this->foreignKeyExists('fraud_events', 'escalated_to')) {
                $table->foreign('escalated_to')->references('id')->on('users')->onDelete('set null');
            }
        });

        $this->dropUnwantedIndexes('mysql');
    }

    private function fixPostgreSql(): void
    {
        Schema::table('fraud_events', function (Blueprint $table) {
            if (! $this->foreignKeyExists('fraud_events', 'order_id')) {
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            }

            if (! $this->foreignKeyExists('fraud_events', 'user_id')) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }

            if (! $this->foreignKeyExists('fraud_events', 'ticket_id')) {
                $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('set null');
            }

            if (! $this->foreignKeyExists('fraud_events', 'event_id')) {
                $table->foreign('event_id')->references('id')->on('events')->onDelete('set null');
            }

            if (! $this->foreignKeyExists('fraud_events', 'first_check_in_by')) {
                $table->foreign('first_check_in_by')->references('id')->on('users')->onDelete('set null');
            }

            if (! $this->foreignKeyExists('fraud_events', 'second_check_in_by')) {
                $table->foreign('second_check_in_by')->references('id')->on('users')->onDelete('set null');
            }

            if (! $this->foreignKeyExists('fraud_events', 'reviewed_by')) {
                $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            }

            if (! $this->foreignKeyExists('fraud_events', 'escalated_to')) {
                $table->foreign('escalated_to')->references('id')->on('users')->onDelete('set null');
            }
        });

        $this->dropUnwantedIndexes('pgsql');
    }

    private function dropUnwantedIndexes(string $driver): void
    {
        $indexesToKeep = [
            'idx_fraud_user_created',
            'idx_fraud_status_created',
            'idx_fraud_risk_created',
            'idx_fraud_order_type_unique',
            'idx_fraud_event_detected',
            'idx_fraud_reviewer_status',
            'idx_fraud_created_at',
            'idx_fraud_ip_address',
            'idx_fraud_card_fingerprint',
        ];

        $allIndexes = [
            'idx_fraud_authentication_method',
            'idx_fraud_chargeback_flag',
            'idx_fraud_payment_intent_id',
            'idx_fraud_proxy_vpn',
            'idx_fraud_detection_risk',
            'idx_fraud_archived_created',
            'idx_fraud_user_email',
            'idx_fraud_risk_status_created',
            'idx_fraud_ticket_quantity',
            'idx_fraud_billing_country',
            'idx_fraud_order_total',
            'idx_fraud_device_fingerprint',
            'idx_fraud_card_country',
            'idx_fraud_archived',
        ];

        foreach ($allIndexes as $idx) {
            if (! in_array($idx, $indexesToKeep, true)) {
                if ($this->indexExists('fraud_events', $idx)) {
                    if ($driver === 'mysql') {
                        DB::statement("DROP INDEX $idx ON fraud_events");
                    } else {
                        DB::statement("DROP INDEX $idx");
                    }
                }
            }
        }
    }

    public function down(): void
    {
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
