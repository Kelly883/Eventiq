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
        if (Schema::hasTable('localization_preferences')) {
            return;
        }

        Schema::create('localization_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('language')->default('en');
            $table->string('region')->nullable();
            $table->string('date_format')->default('MM/DD/YYYY');
            $table->string('time_format')->default('12-hour');
            $table->string('currency')->nullable();
            $table->string('number_format')->default('period');
            $table->boolean('rtl_enabled')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('localization_preferences');
    }
};
