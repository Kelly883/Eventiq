<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inventory_adjustments')) {
            Schema::create('inventory_adjustments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('event_id')->constrained()->onDelete('cascade');
                $table->foreignId('ticket_tier_id')->constrained()->onDelete('cascade');
                $table->foreignUuid('pricing_window_id')->nullable()->constrained('pricing_windows')->onDelete('set null');
                $table->uuid('organizer_id')->constrained('users')->onDelete('cascade');
                $table->foreignUuid('ticket_inventory_id')->nullable()->constrained('ticket_inventory')->onDelete('cascade');
                $table->string('adjustment_type', 50);
                $table->integer('quantity_before');
                $table->integer('quantity_after');
                $table->integer('quantity_delta');
                $table->string('reason', 500)->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index(['event_id', 'created_at'], 'idx_inventory_adjustments_event_created');
                $table->index('organizer_id');
            });

            return;
        }

        $existing = DB::table('inventory_adjustments')->get();

        Schema::dropIfExists('inventory_adjustments');

        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('ticket_tier_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('pricing_window_id')->nullable()->constrained('pricing_windows')->onDelete('set null');
            $table->uuid('organizer_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('ticket_inventory_id')->nullable()->constrained('ticket_inventory')->onDelete('cascade');
            $table->string('adjustment_type', 50);
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->integer('quantity_delta');
            $table->string('reason', 500)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['event_id', 'created_at'], 'idx_inventory_adjustments_event_created');
            $table->index('organizer_id');
        });

        foreach ($existing as $row) {
            DB::table('inventory_adjustments')->insert([
                'id' => $row->id,
                'event_id' => $row->event_id,
                'ticket_tier_id' => $row->ticket_tier_id,
                'pricing_window_id' => $row->pricing_window_id,
                'organizer_id' => $row->organizer_id,
                'ticket_inventory_id' => $row->ticket_inventory_id,
                'adjustment_type' => $row->adjustment_type,
                'quantity_before' => $row->quantity_before,
                'quantity_after' => $row->quantity_after,
                'quantity_delta' => $row->quantity_delta,
                'reason' => $row->reason,
                'created_at' => $row->created_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
