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
        Schema::create('ticket_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('ticket_tier_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('pricing_window_id')->nullable()->constrained('pricing_windows')->onDelete('cascade');
            $table->integer('total_quantity')->default(0);
            $table->integer('sold_quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_inventory');
    }
};
