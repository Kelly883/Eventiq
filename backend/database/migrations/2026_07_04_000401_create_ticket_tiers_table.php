<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('capacity')->nullable();
            $table->integer('min_purchase')->default(1);
            $table->integer('max_purchase')->nullable();
            $table->decimal('early_bird_price', 10, 2)->nullable();
            $table->timestamp('early_bird_end_date')->nullable();
            $table->json('benefits')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_tiers');
    }
};
