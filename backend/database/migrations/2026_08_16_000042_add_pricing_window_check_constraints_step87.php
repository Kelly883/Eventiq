<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // SQLite does not support adding CHECK constraints via ALTER TABLE;
            // non-negativity remains enforced at the application layer.
            return;
        }

        $checks = [
            'quantity_sold_non_negative' => 'quantity_sold >= 0',
            'price_non_negative'         => 'price >= 0',
            'priority_non_negative'      => 'priority >= 0',
        ];

        foreach ($checks as $name => $expression) {
            if (! $this->checkConstraintExists('pricing_windows', $name)) {
                DB::statement("ALTER TABLE pricing_windows ADD CONSTRAINT {$name} CHECK ({$expression})");
            }
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $names = ['quantity_sold_non_negative', 'price_non_negative', 'priority_non_negative'];

        if (DB::getDriverName() === 'pgsql') {
            foreach ($names as $name) {
                DB::statement("ALTER TABLE pricing_windows DROP CONSTRAINT IF EXISTS {$name}");
            }

            return;
        }

        foreach ($names as $name) {
            if ($this->checkConstraintExists('pricing_windows', $name)) {
                DB::statement("ALTER TABLE pricing_windows DROP CHECK {$name}");
            }
        }
    }

    private function checkConstraintExists(string $table, string $name): bool
    {
        if (DB::getDriverName() === 'pgsql') {
            $row = DB::selectOne(
                "SELECT 1 FROM pg_constraint c
                 JOIN pg_class t ON c.conrelid = t.oid
                 JOIN pg_namespace n ON t.relnamespace = n.oid
                 WHERE n.nspname = current_schema()
                   AND t.relname = ?
                   AND c.conname = ?
                   AND c.contype = 'c'",
                [$table, $name]
            );

            return $row !== null;
        }

        if (DB::getDriverName() === 'mysql') {
            $row = DB::selectOne(
                'SELECT constraint_name FROM information_schema.table_constraints
                 WHERE table_schema = DATABASE()
                   AND table_name = ?
                   AND constraint_type = \'CHECK\'
                   AND constraint_name = ?',
                [$table, $name]
            );

            return $row !== null;
        }

        return false;
    }
};
