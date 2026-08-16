<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('processed_webhook_events')) {
            return;
        }

        Schema::create('processed_webhook_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('webhook_id');
            $table->string('event');
            $table->string('gateway_reference')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['webhook_id', 'event', 'gateway_reference']);
            $table->index(['webhook_id', 'status']);
            $table->index('processed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processed_webhook_events');
    }
};
