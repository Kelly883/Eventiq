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
        if (Schema::hasTable('webhooks')) {
            return;
        }

        Schema::create('webhooks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organizer_id');
            $table->string('url');
            $table->string('secret');
            $table->json('subscribed_events');
            $table->string('status')->default('active');
            $table->timestamp('last_failure_at')->nullable();
            $table->integer('failure_count')->default(0);
            $table->timestamps();

            $table->foreign('organizer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index('organizer_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
