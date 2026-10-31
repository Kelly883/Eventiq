<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        if (Schema::hasTable('user_dashboard_preferences')) {
            return;
        }

        $isPostgres = Schema::getConnection()->getDriverName() === 'pgsql';
        Schema::create('user_dashboard_preferences', function (Blueprint $table) use ($isPostgres) {
            if ($isPostgres) {
                $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('user_id');
            $table->string('default_ticket_filter')->default('all');
            $table->string('default_date_range')->default('30days');
            $table->boolean('show_recommendations')->default(true);
            $table->boolean('show_activity_feed')->default(true);
            $table->boolean('auto_refresh_enabled')->default(true);
            $table->timestamps();

            $table->unique('user_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_dashboard_preferences');
    }
};