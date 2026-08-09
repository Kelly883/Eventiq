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

        $duplicateFks = [];
        $seen = [];
        foreach ($fks as $fk) {
            $key = $fk->from . '->' . $fk->table . '.' . $fk->to;
            if (isset($seen[$key])) {
                $duplicateFks[] = $fk->from;
            }
            $seen[$key] = true;
        }

        if (! empty($duplicateFks)) {
            DB::statement('PRAGMA foreign_keys = OFF');

            try {
                DB::transaction(function () use ($duplicateFks) {
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

                    $fkMap = [
                        'order_id' => ['table' => 'orders', 'column' => 'id', 'on_delete' => 'CASCADE'],
                        'user_id' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'CASCADE'],
                        'ticket_id' => ['table' => 'tickets', 'column' => 'id', 'on_delete' => 'SET NULL'],
                        'event_id' => ['table' => 'events', 'column' => 'id', 'on_delete' => 'SET NULL'],
                        'first_check_in_by' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'SET NULL'],
                        'second_check_in_by' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'SET NULL'],
                        'reviewed_by' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'SET NULL'],
                        'escalated_to' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'SET NULL'],
                    ];

                    foreach ($fkMap as $from => $ref) {
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
        $duplicateFks = [
            'fraud_events_ticket_id_foreign',
            'fraud_events_first_check_in_by_foreign',
            'fraud_events_second_check_in_by_foreign',
        ];

        foreach ($duplicateFks as $fk) {
            try {
                DB::statement("ALTER TABLE fraud_events DROP FOREIGN KEY $fk");
            } catch (\Exception $e) {
            }
        }

        $indexesToDrop = [
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

        foreach ($indexesToDrop as $idx) {
            try {
                DB::statement("DROP INDEX $idx ON fraud_events");
            } catch (\Exception $e) {
            }
        }
    }

    public function down(): void
    {
    }
};
