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
        Schema::create('tier_performances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('ticket_tier_id')->constrained()->onDelete('cascade');
            $table->integer('tickets_sold')->default(0);
            $table->decimal('revenue', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['event_id', 'ticket_tier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tier_performances');
    }
};
