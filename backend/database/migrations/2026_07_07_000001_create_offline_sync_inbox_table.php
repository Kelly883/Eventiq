<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_sync_inbox', function (Blueprint $table) {
            $table->id();

            // Idempotency key components
            $table->string('client_id');
            $table->string('op_type');
            $table->string('entity_id');
            $table->string('client_mutation_id');

            // Lifecycle
            $table->string('status')->default('queued'); // queued|processing|applied|conflict|failed
            $table->integer('attempts')->default(0);
            $table->timestamp('next_retry_at')->nullable();

            // Payload
            $table->json('payload');
            $table->json('client_context')->nullable();

            // Server-side bookkeeping for conflict resolution
            $table->unsignedBigInteger('applied_revision')->nullable();
            $table->json('server_state')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['client_id', 'op_type', 'entity_id', 'client_mutation_id'],
                'offline_sync_inbox_idempotency_unique'
            );
            $table->index(['status', 'next_retry_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_sync_inbox');
    }
};


