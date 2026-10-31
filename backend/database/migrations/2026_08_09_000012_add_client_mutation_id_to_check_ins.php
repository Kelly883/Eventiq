<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Disable the implicit transaction wrapper for this migration.
    // On managed PostgreSQL (Render, RDS, etc.), a prior statement failure
    // in the same transaction aborts the entire transaction, causing
    // subsequent CREATE TABLE/ALTER TABLE to fail with "current transaction is aborted".
    // By opting out, each statement runs in its own autocommit transaction.
    public $withinTransaction = false;
    public function up(): void
    {
        $hasCheckInsClientMutationId = Schema::hasColumn('check_ins', 'client_mutation_id');
        Schema::table('check_ins', function (Blueprint $table) use ($hasCheckInsClientMutationId) {
            if (! $hasCheckInsClientMutationId) {
                $table->string('client_mutation_id')->nullable()->after('id');
            }
        });
    }

    public function down(): void
    {
        $hasCheckInsClientMutationId = Schema::hasColumn('check_ins', 'client_mutation_id');
        Schema::table('check_ins', function (Blueprint $table) use ($hasCheckInsClientMutationId) {
            if ($hasCheckInsClientMutationId) {
                $table->dropColumn('client_mutation_id');
            }
        });
    }
};
