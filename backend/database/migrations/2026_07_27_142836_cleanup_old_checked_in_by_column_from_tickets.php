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
     * Removes the old `checked_in_by` integer column that was added by
     * migration 2026_07_06_124000, but only if it is still an integer.
     * If Step 71 or Step 66 reconcile already converted it to UUID,
     * this migration does nothing.
     */
    public function up(): void
    {
        $hasTicketsCheckedInBy = Schema::hasColumn('tickets', 'checked_in_by');
        Schema::table('tickets', function (Blueprint $table) use ($hasTicketsCheckedInBy) {
            if (! $hasTicketsCheckedInBy) {
                return;
            }

            $driver = DB::getDriverName();
            $isInteger = false;

            if ($driver === 'sqlite') {
                $columns = DB::select('PRAGMA table_info(tickets)');
                foreach ($columns as $col) {
                    if ($col->name === 'checked_in_by') {
                        $isInteger = stripos($col->type, 'int') !== false;
                        break;
                    }
                }
            } elseif ($driver === 'pgsql') {
                $row = DB::selectOne(
                    'SELECT data_type FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ? AND column_name = ?',
                    ['tickets', 'checked_in_by']
                );
                if ($row && !empty($row->data_type)) {
                    $isInteger = stripos($row->data_type, 'int') !== false;
                }
            } else {
                $columns = DB::select('SHOW COLUMNS FROM tickets WHERE Field = ?', ['checked_in_by']);
                if (! empty($columns)) {
                    $isInteger = stripos($columns[0]->Type, 'int') !== false;
                }
            }

            if (! $isInteger) {
                return;
            }

            try {
                $table->dropForeign(['checked_in_by']);
            } catch (\Throwable $e) {
                // FK may not exist
            }

            $table->dropColumn('checked_in_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('tickets', 'checked_in_by')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->unsignedBigInteger('checked_in_by')->nullable()->after('checked_in_at');
            });
        }
    }
};