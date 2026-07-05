<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('ticket_tier_id')->constrained()->onDelete('cascade');
            $table->foreignId('pricing_window_id')->nullable()->constrained('pricing_windows')->onDelete('cascade');
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
